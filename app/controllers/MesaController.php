<?php
date_default_timezone_set("America/Buenos_Aires");
require_once './models/Mesa.php';
require_once './models/MesaEstado.php';
require_once './models/UsuarioAccionTipo.php';
require_once './models/Usuario.php';
require_once './models/UsuarioTipo.php';

require_once './interfaces/IApiUsable.php';

use \App\Models\Usuario as Usuario;
use \App\Models\UsuarioTipo as UsuarioTipo;

use \App\Models\Mesa as Mesa;
use \App\Models\MesaEstado as MesaEstado;
use \App\Models\UsuarioAccionTipo as UsuarioAccionTipo;
use Illuminate\Database\Capsule\Manager as DB;

class MesaController implements IApiUsable
{
  public function GetAll($request, $response, $args)
  {
    $lista = Mesa::all();
    $payload = json_encode(array("listaMesa" => $lista));

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function GetAllBy($request, $response, $args)
  {
    $field = $args['field'];
    $value = $args['value'];

    $lista = Mesa::where($field, $value)->get();

    $payload = json_encode(array("listaMesa" => $lista));

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }
  
  public function GetFirstBy($request, $response, $args)
  {
    $field = $args['field'];
    $value = $args['value'];

    $obj = Mesa::where($field, $value)->first();

    $payload = json_encode($obj);

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function Save($request, $response, $args)
  {
    try
    {
      $idUsuarioLogeado = AutentificadorJWT::GetUsuarioLogeado($request)->id;
      $data = $request->getParsedBody();
      if (!isset($data['descripcion'])) { throw new Exception("Descripción de Mesa no seteada"); }
      
      $mesaNew = new Mesa();
      $mesaNew->idMesaEstado = MesaEstado::Cerrada;
      $mesaNew->codigo = Mesa::GenerarCodigoAlfanumerico();
      $mesaNew->descripcion = $data['descripcion'];
      $mesaNew->save();
      
      $payload = json_encode(
      array(
      "mensaje" => "Mesa creada con éxito",
      "idUsuario" => $idUsuarioLogeado,
      "idUsuarioAccionTipo" => UsuarioAccionTipo::Alta,
      "idPedido" => null, 
      "idPedidoDetalle" => null, 
      "idMesa" => null, 
      "idProducto" => null, 
      "idArea" => null,
      "hora" => date('h:i:s'))
      );
      
      $response->getBody()->write($payload);
      return $response->withHeader('Content-Type', 'application/json');
    }
    catch(Exception $e){
      $response = $response->withStatus(401);
      $response->getBody()->write(json_encode(array('error' => $e->getMessage())));
      return $response->withHeader('Content-Type', 'application/json');
    }
  }

  public function Update($request, $response, $args)
  {
    try
    {
      $idUsuarioLogeado = AutentificadorJWT::GetUsuarioLogeado($request)->id;
      $obj = Mesa::find($args['id']);
      if ($obj == null) { throw new Exception("No existe Mesa con el id indicado"); }

      $data = $request->getParsedBody();

      if (isset($data['idMesaEstado']) && MesaEstado::find($data['idMesaEstado']) != null) 
      {
        if($data['idMesaEstado'] == MesaEstado::Cerrada 
        && $idUsuarioLogeado != UsuarioTipo::Administrador 
        && $idUsuarioLogeado != UsuarioTipo::Socio){
          throw new Exception("No tienes acceso a Cerrar Mesa, sólo Socio o Administrador");
        }
        $obj->idMesaEstado = $data['idMesaEstado']; 
      } 

      if (isset($data['descripcion'])) { $obj->descripcion = $data['descripcion']; }

      $obj->save();
      $payload = json_encode(
      array(
      "mensaje" => "Mesa modificada con éxito",
      "idUsuario" => $idUsuarioLogeado,
      "idUsuarioAccionTipo" => UsuarioAccionTipo::Modificacion,
      "idPedido" => null, 
      "idPedidoDetalle" => null, 
      "idMesa" => $obj->id, 
      "idProducto" => null, 
      "idArea" => null,
      "hora" => date('h:i:s')
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

  public function Delete($request, $response, $args)
  {
    try
    {
      $obj = Mesa::find($args['id']);
      if ($obj == null) { throw new Exception("No existe Mesa con el id indicado, puede que se haya dado de baja anteriormente"); }
      
      $obj->delete();
      
      $idUsuarioLogeado = AutentificadorJWT::GetUsuarioLogeado($request)->id;
      $payload = json_encode(
        array(
        "mensaje" => "Mesa dada de baja con éxito",
        "idUsuario" => $idUsuarioLogeado,
        "idUsuarioAccionTipo" => UsuarioAccionTipo::Baja,
        "idPedido" => null, 
        "idPedidoDetalle" => null, 
        "idMesa" => $obj->id, 
        "idProducto" => null, 
        "idArea" => null,
        "hora" => date('h:i:s')
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
