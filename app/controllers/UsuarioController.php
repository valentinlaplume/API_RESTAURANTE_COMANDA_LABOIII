<?php
require_once './models/Usuario.php';
require_once './models/UsuarioTipo.php';
require_once './interfaces/IApiUsable.php';

use \App\Models\Usuario as Usuario;
use \App\Models\UsuarioTipo as UsuarioTipo;

class UsuarioController implements IApiUsable
{
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
    $data = $request->getParsedBody();
    $idUsuarioTipo = $data['idUsuarioTipo'];
    if($idUsuarioTipo == UsuarioTipo::Administrador){
      $payload = json_encode(array("mensaje" => "No es válido crear administrador"));
    }

    $idArea = $data['idArea'];
    $usuario = $data['usuario'];
    $clave = $data['clave'];

    $usr = new Usuario();
    $usr->idUsuarioTipo = $idUsuarioTipo;
    $usr->idArea = $idArea;
    $usr->usuario = $usuario;
    $usr->clave = $clave;
    $usr->save();

    $payload = json_encode(array("mensaje" => "Usuario creado con exito"));
    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
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
