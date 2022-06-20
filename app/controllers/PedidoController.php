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
require_once './models/ManejadorArchivos.php';

require ('./fpdf/fpdf.php');



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
use Illuminate\Database\Capsule\Manager as DB;

class PedidoController implements IApiUsable
{
  public function GetAllBandejaPedidosPendientes($request, $response, $args)
  {
    try
    {
      $usuarioLogeado = AutentificadorJWT::GetUsuarioLogeado($request);
      
      // Si es Administrador, Socio o Mozo, ve los pedidos Generales
      if ($usuarioLogeado->idUsuarioTipo == UsuarioTipo::Administrador || 
          $usuarioLogeado->idUsuarioTipo == UsuarioTipo::Socio || 
          $usuarioLogeado->idUsuarioTipo == UsuarioTipo::Mozo) {

          $listPendientes = Pedido::where('idPedidoEstado', PedidoEstado::Pendiente)->get();

          $payload = json_encode(array(
          'mensaje' => 'Lista de Pedidos tomados pendientes',
          "listPendientes" => $listPendientes));

          $response->getBody()->write($payload);
          return $response->withHeader('Content-Type', 'application/json');
      }

      $query =
      'SELECT 
        pD.id as idPedidoDetalle,
        p.id as idPedido,
        pro.id as idProducto,
        pE.id as idPedidoEstado,
        p.idUsuarioMozo as idUsuarioMozo,
        p.idMesa as idMesa,
        IFNULL(pD.idUsuarioEncargado, "Espearando empleado que tome pedido") as idUsuarioEncargado,
        p.codigo as codigoPedidoGeneral,
        m.codigo as codigoMesa,
        a.descripcion as areaProducto,
        u.usuario as usuarioMozoEncargado,
        pro.nombre as nombreProducto,
        pD.cantidadProducto as cantidadProducto,
        pE.estado as estadoPedido
      FROM PedidoDetalle pD
      INNER JOIN Pedido p ON p.id = pD.idPedido
      INNER JOIN Usuario u ON u.id = p.idUsuarioMozo
      INNER JOIN Producto pro ON pro.id = pD.idProducto
      INNER JOIN PedidoEstado pE ON pE.id = pD.idPedidoEstado 
      INNER JOIN Area a ON a.id = pro.idArea
      INNER JOIN Mesa m ON m.id = p.idMesa 
      WHERE a.id = ' . $usuarioLogeado->idArea .
      ' AND pE.id <> '. PedidoEstado::Listo_Para_Servir .
      ' AND pE.id <>' . PedidoEstado::Cancelado . 
      ' AND pE.id <> '. PedidoEstado::Servido;

      $list = DB::select($query);

      $payload = json_encode(array(
      'mensaje' => 'Lista de Pedidos Pendientes o en Preparación por Area del Usuario Logeado',
      "listPedidosPendientes" => $list));
  
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }catch(Exception $e){
      $response = $response->withStatus(401);
      $response->getBody()->write(json_encode(array('error' => $e->getMessage())));
      return $response->withHeader('Content-Type', 'application/json');
    }
  }

  public function GetAllPedidoDetalleCliente($request, $response, $args)
  {
    try
    {
      $codigoPedido = isset($args['codigoPedido']) ? $args['codigoPedido'] : null;
      $codigoMesa = isset($args['codigoMesa']) ? $args['codigoMesa'] : null;
      if($codigoPedido == null  || $codigoMesa == null){ throw new Exception('Al menos un dato de consulta no fue seteado.'); }

      $mesa = Mesa::where('codigo',  $codigoMesa)->first();
      if($mesa == null){ throw new Exception('Mesa no encontrada.'); }

      $query =
      'SELECT 
        pD.id as idPedidoDetalle,
        pro.id as idProducto,
        pE.id as idPedidoEstado,
        p.idUsuarioMozo as idUsuarioMozo,
        p.idMesa as idMesa,
        IFNULL(pD.idUsuarioEncargado, "Espearando empleado que tome pedido") as idUsuarioEncargado,
        m.codigo as codigoMesa,
        u.usuario as usuarioMozoEncargado,
        pro.nombre as nombreProducto,
        pD.cantidadProducto as cantidadProducto,
        pE.estado as estadoPedido,
        pro.precio as precioProducto,
        IFNULL(pD.tiempoEstimado, "No disponible hasta que un empleado tome pedido e indique") as tiempoEstimado
      FROM PedidoDetalle pD
      INNER JOIN Pedido p ON p.id = pD.idPedido
      INNER JOIN Usuario u ON u.id = p.idUsuarioMozo
      INNER JOIN Producto pro ON pro.id = pD.idProducto
      INNER JOIN PedidoEstado pE ON pE.id = pD.idPedidoEstado 
      INNER JOIN Mesa m ON m.id = p.idMesa 
      WHERE  p.idMesa = ' . $mesa->id . ' AND p.codigo = ' ."'". $codigoPedido ."'";

      $list = DB::select($query);

      $payload = json_encode(array(
        'mensaje' => 'Seguimiento de estado de Pedido del Cliente',
        'PedidoGeneral' => (Pedido::where('codigo', $codigoPedido)->first()),
        "listDetallePedidoGeneral" => $list));

      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }
    catch(Exception $e){
      $response = $response->withStatus(401);
      $response->getBody()->write(json_encode(array('error' => $e->getMessage())));
      return $response->withHeader('Content-Type', 'application/json');
    }
  }

  public function GetAll($request, $response, $args)
  {
    $lista = Pedido::all();
    $payload = json_encode(array(
      'mensaje' => 'Listado de Pedidos Generales',
      "listaPedido" => $lista));

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function GetAllBy($request, $response, $args)
  {
    $field = $args['field'];
    $value = $args['value'];

    $lista = Pedido::where($field, $value)->get();

    $payload = json_encode(array(
      'mensaje' => 'Consulta de Pedido General por ' . $args['field'],
      "listaPedido" => $lista));

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

  public function SaveFoto($request, $response, $args)
  {
    try
    {
      $idUsuarioLogeado = AutentificadorJWT::GetUsuarioLogeado($request)->id;
      if(!isset($_FILES) || !isset($_POST['codigoPedido'])) { throw new Exception('La foto o el código del Pedido no fueron seteados.'); }

      $obj = Pedido::where('codigo','=',$_POST['codigoPedido'])->first();
      if($obj == null){ throw new Exception('El código ingresado no le pertenece a ningún Pedido.'); }

      $directory = './imagenes/clientes';
      $fileName = $obj->id;
      if(!ManejadorArchivos::SaveImage($directory, $fileName, $_FILES))
      { throw new Exception('No fue posible guardar imagen de la Mesa con los Clientes en el Pedido '.$_POST['codigoPedido'] ); }

      $obj->foto = './imagenes/clientes/' . $obj->id . '.png';
      $obj->save();

      $payload = json_encode(
      array(
      "mensaje" => "Foto de Mesa con Clientes guardada con éxito",
      "idUsuario" => $idUsuarioLogeado,
      "idUsuarioAccionTipo" => UsuarioAccionTipo::CargaFoto,
      "idPedido" => $obj->id, 
      "idPedidoDetalle" => null, 
      "idMesa" => null, 
      "idProducto" => null, 
      "idArea" => null,
      "hora" => date('h:i:s')));

      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }
    catch(Exception $e){
      $response = $response->withStatus(401);
      $response->getBody()->write(json_encode(array("error" => $e->getMessage())));
      return $response->withHeader('Content-Type', 'application/json');
    }
  }

  public function Save($request, $response, $args)
  {
    try
    {
      $idUsuarioLogeado = AutentificadorJWT::GetUsuarioLogeado($request)->id;
      // ---------------- Junto Data ----------------
      $data = $request->getParsedBody();

      $nombreCliente = isset($data['nombreCliente']) ? $data['nombreCliente'] : null;
      // ---------------- Modifico estado de mesa ----------------
      $codigoMesa = isset($data['codigoMesa']) ? $data['codigoMesa'] : null;

      if($codigoMesa == null)
      { 
        $mesa = Mesa::where('idMesaEstado', MesaEstado::Cerrada)->first(); 
        if($mesa == null){ throw new Exception('Capacidad llena, no se encuentran Mesas disponibles.'); }
      }
      else{
        $mesa = Mesa::where('codigo', $codigoMesa)->first();
        if($mesa == null){ throw new Exception('Mesa no encontrada, verifique código.'); }
        if($mesa->idMesaEstado != MesaEstado::Cerrada){ throw new Exception('Mesa ocupada.'); }
      }
      
      $listPedidoDetalle = $data['listPedidoDetalle'];
      $importeTotal = self::GetImporteTotal($listPedidoDetalle);
      // ---------------- Save Pedido General ----------------
      $pedido = new Pedido();
      $pedido->codigo = Pedido::GenerarCodigoAlfanumerico();
      $pedido->idMesa = $mesa->id;
      $pedido->idPedidoEstado = PedidoEstado::Pendiente; 
      $pedido->idUsuarioMozo = $idUsuarioLogeado; 
      if($nombreCliente !== null){ $pedido->nombreCliente = $nombreCliente; } else{ $pedido->nombreCliente = " "; }
      $pedido->foto = null;
      $pedido->importe = $importeTotal;
      $pedido->save();
      
      // ---------------- Save Detalle de Pedido General ----------------
      foreach ($listPedidoDetalle as $detalle) 
      {
        $pedidoDetalle = new PedidoDetalle();
        $pedidoDetalle->idPedido = $pedido->id;
        $pedidoDetalle->idProducto =  $detalle['idProducto'];
        $pedidoDetalle->idPedidoEstado = PedidoEstado::Pendiente;
        $pedidoDetalle->cantidadProducto =  $detalle['cantidadProducto'];
        // $pedidoDetalle->tiempoInicio = date('Y-m-d H:i:s', time());
        $pedidoDetalle->save();
      }

      $mesa->idMesaEstado = MesaEstado::Cliente_Esperando_Pedido;
      $mesa->save();

      $payload = json_encode(
      array(
      "mensaje" => "Pedido generado con éxito",
      "idUsuario" => $idUsuarioLogeado,
      "idUsuarioAccionTipo" => UsuarioAccionTipo::Alta,
      "idPedido" => null, 
      "idPedidoDetalle" => null, 
      "idMesa" => null, 
      "idProducto" => null, 
      "idArea" => null,
      "hora" => date('h:i:s')));

      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }
    catch(Exception $e)
    {
      $response = $response->withStatus(401);
      $response->getBody()->write(json_encode(array("error" => $e->getMessage())));
      return $response->withHeader('Content-Type', 'application/json');
    }
  }

  static private function GetImporteTotal($arr = array())
  {
    $importeTotal = 0;
    foreach ($arr as $pedidoDetalle) {
      $importeTotal += floatval(Producto::where('id', $pedidoDetalle['idProducto'])->first()->precio);
    }
    return $importeTotal;
  }

  public function Update($request, $response, $args)
  {
    try
    {
      $obj = Pedido::find($args['id']);
      if($obj == null) { throw new Exception('Pedido no encontrado.'); }
      $usuarioLogeado = AutentificadorJWT::GetUsuarioLogeado($request);

      if($usuarioLogeado->idUsuarioTipo != UsuarioTipo::Administrador 
      && $usuarioLogeado->idUsuarioTipo != UsuarioTipo::Socio 
      && $obj->idUsuarioMozo != $usuarioLogeado->id) { throw new Exception('Acceso a modificar pedido sólo Mozo encargado, Administrador o Socios.');  }

      $data = $request->getParsedBody();
      $modificar = false;

      if (isset($data['idMesa']) && Mesa::find($data['idMesa']) != null) { 
        if(Mesa::find($data['idMesa'])->idMesaEstado != MesaEstado::Cerrada){ throw new Exception('No es posible modificar Mesa, se encuentra ocupada.'); }
        $obj->idMesa = intval($data['idMesa']); 
        $modificar = true;
      }

      if (isset($data['idUsuarioMozo'])) { 
        if(Usuario::find($data['idUsuarioMozo']) == null) { throw new Exception('id Mozo encargado a modificar no existe'); }
        if(Usuario::find($data['idUsuarioMozo'])->UsuarioTipo->id != UsuarioTipo::Mozo) { throw new Exception('No es posible modificar pedido, sólo Mozos tienen permitido encargase del pedido general'); }
        $obj->idUsuarioMozo = intval($data['idUsuarioMozo']); 
        $modificar = true;
      }

      if (isset($data['idArea']) && Area::find($data['idArea']) != null) { $obj->idArea = intval($data['idArea']); $modificar = true; }
      if (isset($data['idPedidoEstado']) && PedidoEstado::find($data['idPedidoEstado']) != null) { $obj->idPedidoEstado = intval($data['idPedidoEstado']); $modificar = true; }
      if (isset($data['nombreCliente']) != null) { $obj->nombreCliente = $data['nombreCliente']; $modificar = true; }
      
      if(!$modificar){ throw new Exception('El Pedido no fue modificado, verifique de indicar campos válidos'); }
      
      $obj->save();

      $payload = json_encode(
      array(
      "mensaje" => "Pedido modificado con éxito",
      "idUsuario" => $usuarioLogeado->id,
      "idUsuarioAccionTipo" => UsuarioAccionTipo::Modificacion,
      "idPedido" => $obj->id, 
      "idPedidoDetalle" => null, 
      "idMesa" => null, 
      "idProducto" => null, 
      "idArea" => null,
      "hora" => date('h:i:s')));
  
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }
    catch(Exception $e){
      $response = $response->withStatus(401);
      $response->getBody()->write(json_encode(array("error" => $e->getMessage())));
      return $response->withHeader('Content-Type', 'application/json');
    }
  }

  public function UpdatePedidoDetalle($request, $response, $args)
  {
    try
    {
      $obj = PedidoDetalle::find($args['idPedidoDetalle']);
      if($obj == null) { throw new Exception('Detalle del Pedido no encontrado.'); }
      $usuarioLogeado = AutentificadorJWT::GetUsuarioLogeado($request);

      if(Producto::find($obj->idProducto)->idArea != $usuarioLogeado->idArea) 
      { throw new Exception('No tienes acceso a modificar Detalle del Pedido, pertenece al Area de '. Producto::find($obj->idProducto)->Area->descripcion); }

      $data = $request->getParsedBody();
      $modificar = false;


      if (isset($data['idPedidoEstado']) && PedidoEstado::find($data['idPedidoEstado']) != null) { 
        $obj->idPedidoEstado = intval($data['idPedidoEstado']); 
        if($data['idPedidoEstado'] == PedidoEstado::En_Preparacion) { 
          $obj->idUsuarioEncargado = 10;//intval($usuarioLogeado->id);
          $obj->tiempoInicio = date('h:i:s'); 
        }
        if($data['idPedidoEstado'] == PedidoEstado::Listo_Para_Servir) { $obj->tiempoFin = date('h:i:s'); }
        $modificar = true;
      }

      if (isset($data['tiempoEstimado'])) { 
        $obj->tiempoEstimado = intval($data['tiempoEstimado']); 
        $modificar = true;
      }

      if(!$modificar){ throw new Exception('El Detalle del Pedido no fue modificado, verifique de indicar campos válidos'); }
      
      $obj->save();

      $payload = json_encode(
      array(
      "mensaje" => "Detalle del Pedido modificado con éxito",
      "idUsuario" => $usuarioLogeado->id,
      "idUsuarioAccionTipo" => UsuarioAccionTipo::Modificacion,
      "idPedido" => null, 
      "idPedidoDetalle" => $obj->id, 
      "idMesa" => null, 
      "idProducto" => null, 
      "idArea" => null,
      "hora" => date('h:i:s')));
  
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }
    catch(Exception $e){
      $response = $response->withStatus(401);
      $response->getBody()->write(json_encode(array("error" => $e->getMessage())));
      return $response->withHeader('Content-Type', 'application/json');
    }
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

  static private function GetPedidoMasCaro($pdf)
  {
    $max = Pedido::all()->max('importe');
    $pedido = Pedido::where('importe','=', $max)->first();

    $pdf->Cell(20,10,'---------------- PEDIDO MAS CARO ----------------','C');
    $pdf->SetFont('Arial','B', 10);
    $pdf->Ln(13);
    $pdf->Cell(0,0,'Costo:      $'. $pedido->importe,'C');
    $pdf->Ln(7);
    $pdf->Cell(0,0,'Codigo del Pedido:     '. $pedido->codigo,'C');
    $pdf->Ln(7);
    $pdf->Cell(0,0,'Codigo de Mesa:     '. $pedido->Mesa->codigo,'C');
    $pdf->Ln(7);
    $pdf->Cell(0,0,'Nombre del Cliente:    '. $pedido->nombreCliente,'C');
    $pdf->Ln(7);
    $pdf->Cell(0,0,'Fecha:     '. $pedido->fechaAlta,'C');
    $pdf->Ln(7);
    // 'Costo: $ '. $pedido->importe .$pdf->Ln(10).
    // 'Codigo del Pedido: '. $pedido->codigo .$pdf->Ln(10).
    // 'Codigo de Mesa: '. $pedido->Mesa->codigo .$pdf->Ln(10).
    // 'Nombre del Cliente: '. $pedido->nombreCliente .$pdf->Ln(10).
    // 'Fecha: '. $pedido->fechaAlta .$pdf->Ln(10);
  }

  public function DescargarReporteMesPDF($request, $response, $args)
  {
    try
    {
      // C:\xampp\htdocs\slim-php-mysql-heroku\app\fpdf\fpdf.php
      $directory = './reportes/';
      if (!file_exists($directory)) { mkdir($directory, 0777, true); }

      $pdf = new FPDF();
      $pdf->AddPage();
      $pdf->SetFont('Arial','B', 14);
      $contenido = '';
      $contenido .= self::GetPedidoMasCaro($pdf);
      $pdf->Cell(40,10,$contenido);

      $fileName = 'reporte_' .date('Ymd_his'). '.pdf';
      $path = $directory . $fileName;

      $pdf->Output('F', $path, 'I');

      $payload = json_encode(array(
        "mensaje" => "Reporte del mes descargado con éxito",
        "rutaReporteDescargado" => $path
      ));
  
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }
    catch(Exception $e){
      $response = $response->withStatus(401);
      $response->getBody()->write(json_encode(array('error' => $e->getMessage())));
      return $response->withHeader('Content-Type', 'application/json');
    }
  }

 
}
