<?php
date_default_timezone_set("America/Buenos_Aires");
require_once './interfaces/IApiUsable.php';
require_once './middlewares/AutentificadorJWT.php';

require_once './models/Pedido.php';
require_once './models/PedidoEstado.php';
require_once './models/PedidoDetalle.php';
require_once './models/Mesa.php';
require_once './models/MesaEstado.php';
require_once './models/Producto.php';
require_once './models/ProductoTipo.php';
require_once './models/Usuario.php';
require_once './models/UsuarioTipo.php';
require_once './models/UsuarioAccion.php';
require_once './models/UsuarioAccionTipo.php';
require_once './models/UsuarioAccionTipo.php';

use \App\Models\Pedido as Pedido;
use \App\Models\PedidoEstado as PedidoEstado;
use \App\Models\PedidoDetalle as PedidoDetalle;
use \App\Models\Mesa as Mesa;
use \App\Models\MesaEstado as MesaEstado;
use \App\Models\Producto as Producto;
use \App\Models\ProductoTipo as ProductoTipo;
use \App\Models\Usuario as Usuario;
use \App\Models\UsuarioTipo as UsuarioTipo;
use \App\Models\UsuarioAccion as UsuarioAccion;
use \App\Models\UsuarioAccionTipo as UsuarioAccionTipo;

use Slim\Psr7\Response;

class PedidoController implements IApiUsable
{
  public function GetAllPendientes($request, $response, $args)
  {
    try{
      $header = $request->getHeaderLine('Authorization');
      $response = new Response();
      $token = trim(explode("Bearer", $header)[1]);
      AutentificadorJWT::VerificarToken($token);
      $data = AutentificadorJWT::ObtenerData($token);

      $obj = Usuario::where('usuario', $data->usuario)->first();
        
      if ($obj->idUsuarioTipo == UsuarioTipo::Administrador || 
          $obj->idUsuarioTipo == UsuarioTipo::Socio || 
          $obj->idUsuarioTipo == UsuarioTipo::Mozo) {
          $listPendientes = Pedido::where('idPedidoEstado', PedidoEstado::Pendiente)->get();

          echo ' - Pedidos Generales: '.PHP_EOL;
          $payload = json_encode(array("listPendientes" => $listPendientes));

          $response->getBody()->write($payload);
          return $response
            ->withHeader('Content-Type', 'application/json');
      }

      // obtengo todos los pedidos Pendientes
      $pedidosPendientes = PedidoDetalle::where('idPedidoEstado', PedidoEstado::Pendiente)->get();

       
      $productosElaborar = array();
      foreach ($pedidosPendientes as $pedidoIndividual)
      {
        // Obtengo pedidos donde el Producto sea del area del usuario logeado
        if($pedidoIndividual->Producto->idArea == $obj->idArea)
        {
          array_push($productosElaborar, $pedidoIndividual);
        }
      }

      $payload = json_encode(array("listPedidoPendientes" => $productosElaborar));
  
      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }catch(Exception $e){
      $response->getBody()->write($e->Message());
      return $response
        ->withHeader('Content-Type', 'application/json');
    }
  }

  public function GetAllPedidoDetalleCliente($request, $response, $args)
  {
    try{
      $codigoPedido = isset($args['codigoPedido']) ? $args['codigoPedido'] : null;
      $codigoMesa = isset($args['codigoMesa']) ? $args['codigoMesa'] : null;
      if($codigoPedido == null  || $codigoMesa == null){ throw new Exception('Al menos un dato de consulta no seteado.'); }

      $pedido = Pedido::where('codigo', $codigoPedido)->first();
      $mesa = Mesa::where('codigo',  $codigoMesa)->first();
      if($pedido == null){ throw new Exception('Pedido no encontrado.'); }
      if($mesa == null){ throw new Exception('Mesa no encontrada.'); }

      $payload = json_encode(array("listPedidoDetalle" => $pedido->ListPedidoDetalle));

      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }catch(Exception $e){
      $response->getBody()->write($e->getMessage());
      return $response->withHeader('Content-Type', 'application/json');
    }
  }


    public function GetAll($request, $response, $args)
    {
      $lista = Pedido::all();
      $payload = json_encode(array("listaPedido" => $lista));

      $response->getBody()->write($payload);
      return $response
        ->withHeader('Content-Type', 'application/json');
    }

  public function GetAllBy($request, $response, $args)
  {
    $field = $args['field'];
    $value = $args['value'];

    $lista = Pedido::where($field, $value)->get();

    $payload = json_encode(array("listaPedido" => $lista));

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function GetFirstBy($request, $response, $args)
  {
    $field = $args['field'];
    $value = $args['value'];

    $obj = Pedido::where($field, $value)->first();

    $payload = json_encode($obj);

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function Save($request, $response, $args)
  {
    try{
      // ---------------- Junto Data ----------------
      $data = $request->getParsedBody();
      
      // ver si es por registro al autenticar
      $idUsuarioMozo = isset($data['idUsuarioMozo']) ? $data['idUsuarioMozo'] : null;
      $nombreCliente = isset($data['nombreCliente']) ? $data['nombreCliente'] : null;
      
      // ---------------- Modifico estado de mesa ----------------
      $codigoMesa = isset($data['codigoMesa']) ? $data['codigoMesa'] : null;

      if($codigoMesa == null) { 
        $mesa = Mesa::where('MesaEstado', MesaEstado::Cerrada)->first(); 
        if($mesa == null){ throw new Exception('No se encuentran Mesas disponibles.'); }
      }else{
        $mesa = Mesa::where('codigo', $codigoMesa)->first();
        if($mesa == null){ throw new Exception('Mesa no encontrada.'); }
        if($mesa->idMesaEstado != MesaEstado::Cerrada){ throw new Exception('Mesa ocupada.'); }
      }

      $mesa->idMesaEstado = MesaEstado::Cliente_Esperando_Pedido;
      $mesa->save();
      
      $listPedidoDetalle = $data['listPedidoDetalle'];
      $importeTotal = self::GetImporteTotal($listPedidoDetalle);
      // ---------------- Save Pedido General ----------------
      $pedido = new Pedido();
      $pedido->codigo = Pedido::GenerarCodigoAlfanumerico();
      $pedido->idMesa = $mesa->id;
      $pedido->idPedidoEstado = PedidoEstado::Pendiente; 
      if($idUsuarioMozo !== null){ $pedido->idUsuarioMozo = $idUsuarioMozo; }
      if($nombreCliente !== null){ $pedido->nombreCliente = $nombreCliente; }
      $pedido->foto = './imagenes/pedido/' . $data['foto'];
      $pedido->importe = $importeTotal;
      $pedido->save();
      
      // ---------------- Save Detalle de Pedido General ----------------
      foreach ($listPedidoDetalle as $detalle) {
        $pedidoDetalle = new PedidoDetalle();
        $pedidoDetalle->idPedido = $pedido->id;
        $pedidoDetalle->idProducto =  $detalle['idProducto'];
        $pedidoDetalle->idPedidoEstado = PedidoEstado::Pendiente;
        $pedidoDetalle->cantidadProducto =  $detalle['cantidadProducto'];
        // $pedidoDetalle->tiempoInicio = date('Y-m-d H:i:s', time());
        $pedidoDetalle->save();
      }

      $payload = json_encode(array("Mensaje" => "Pedido generado correctamente"));
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }catch(Exception $e){
      $response->getBody()->write(json_encode(array("Mensaje" => $e->getMessage())));
      return $response->withHeader('Content-Type', 'application/json');
    }

  }

  static private function GetImporteTotal($arr = array()){
    $importeTotal = 0;
    foreach ($arr as $pedidoDetalle) {
      $importeTotal += floatval(Producto::where('id', $pedidoDetalle['idProducto'])->first()->precio);
    }
    return $importeTotal;
  }

  public function Update($request, $response, $args)
  {
    $data = $request->getParsedBody();

    $idArea = isset($data['idArea']) ? $data['idArea'] : null;
    $idPedidoEstado = isset($data['idPedidoEstado']) ? $data['idPedidoEstado'] : null;
    $nombre = isset($data['nombre']) ? $data['nombre'] : null;
    $precio = isset($data['precio']) ? $data['precio'] : null;
    $stock = isset($data['stock']) ? $data['stock'] : null;

    // Conseguimos el objeto
    $obj = Pedido::where('id', '=', $args['id'])->first();

    if ($obj !== null) {
      if($idArea !== null) { $obj->idArea = $idArea; }
      if($idPedidoEstado !== null) { $obj->idPedidoEstado = $idPedidoEstado; }
      if($nombre !== null) { $obj->nombre = $nombre; }
      if($precio !== null) { $obj->precio = $precio; }
      if($stock !== null) { $obj->stock = $stock; }

      $obj->save();
      $payload = json_encode(array("mensaje" => "Pedido modificado con exito"));
    } 
    else {
      $payload = json_encode(array("mensaje" => "Pedido no encontrado"));
    }

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function Delete($request, $response, $args)
  {
    $obj = Pedido::find($args['id']);
    if ($obj !== null) {
      $obj->pedido = $objModificado;

      $pedido->delete();
      $payload = json_encode(array("mensaje" => "Pedido borrado con exito"));
    } 
    else {
      $payload = json_encode(array("mensaje" => "Pedido no encontrado"));
    }

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }
}
