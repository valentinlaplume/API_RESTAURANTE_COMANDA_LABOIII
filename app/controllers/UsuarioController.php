<?php
date_default_timezone_set("America/Buenos_Aires");
require_once './interfaces/IApiUsable.php';
require_once './middlewares/AutentificadorJWT.php';

require_once './models/Usuario.php';
require_once './models/UsuarioTipo.php';
require_once './models/UsuarioAccion.php';
require_once './models/UsuarioAccionTipo.php';

use \App\Models\Usuario as Usuario;
use \App\Models\UsuarioTipo as UsuarioTipo;
use \App\Models\UsuarioAccion as UsuarioAccion;
use \App\Models\UsuarioAccionTipo as UsuarioAccionTipo;

class UsuarioController implements IApiUsable
{
  public function GetAllUsuarioAccion($request, $response, $args)
  {
    $lista = UsuarioAccion::all();
    $payload = json_encode(array("listaUsuarioAccion" => $lista));

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function GetAll($request, $response, $args)
  {
    $lista = Usuario::all();
    $payload = json_encode(array("listaUsuario" => $lista));

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function GetAllBy($request, $response, $args)
  {
    $field = $args['field'];
    $value = $args['value'];

    $lista = Usuario::where($field, $value)->get();

    $payload = json_encode(array("listaUsuario" => $lista));

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function GetFirstBy($request, $response, $args)
  {
    $field = $args['field'];
    $value = $args['value'];

    $obj = Usuario::where($field, $value)->first();

    $payload = json_encode($obj);

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function Save($request, $response, $args)
  {
    try{

      $idUsuarioLogeado = AutentificadorJWT::GetUsuarioLogeado($request)->id;
      // $response = $handler->handle($request);
      $body = json_decode($response->getBody());
      $header = $request->getHeaderLine('Authorization');

      $data = $request->getParsedBody();

      if(Usuario::ExisteUsuario($data['usuario'])) { throw new Exception("Nombre de Usuario ya existe"); }
        
      $idUsuarioTipo = $data['idUsuarioTipo'];
      $idArea = $data['idArea'];
      $usuario = $data['usuario'];
      $clave = $data['clave'];
        
      $usr = new Usuario();
      $usr->idUsuarioTipo = $idUsuarioTipo;
      $usr->idArea = $idArea;
      $usr->usuario = $usuario;
      $usr->clave = $clave;
      $usr->save();

      $payload = json_encode(
      array(
        "mensaje" => "Usuario creado con éxito",
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
      return $response
      ->withHeader('Content-Type', 'application/json');
    }catch (Exception $e) {
      $response = $response->withStatus(401);
      $response->getBody()->write(json_encode(array('error' => $e->getMessage())));
      return $response->withHeader('Content-Type', 'application/json');
    }
  }
    
    public function Update($request, $response, $args)
    {
      $data = $request->getParsedBody();
      
      $usrModificado = $data['usuario'];

    // Conseguimos el objeto
    $obj = Usuario::where('id', '=', $args['id'])->first();

    if ($obj !== null) {
      $obj->usuario = $usrModificado;

      $obj->save();
      $payload = json_encode(array("mensaje" => "Usuario modificado con exito"));
    } 
    else {
      $payload = json_encode(array("mensaje" => "Usuario no encontrado"));
    }

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function Delete($request, $response, $args)
  {
    $obj = Usuario::find($args['id']);
    if ($obj !== null) {
      $obj->usuario = $usrModificado;

      $usuario->delete();
      $payload = json_encode(array("mensaje" => "Usuario borrado con exito"));
    } 
    else {
      $payload = json_encode(array("mensaje" => "Usuario no encontrado"));
    }

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }


}
