<?php
require_once './models/Mesa.php';
require_once './models/MesaEstado.php';
require_once './interfaces/IApiUsable.php';

use \App\Models\Mesa as Mesa;
use \App\Models\MesaEstado as MesaEstado;

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
    $parametros = $request->getParsedBody();
    $idMesaEstado = $parametros['idMesaEstado'];
    $descripcion = $parametros['descripcion'];
    
    $usr = new Mesa();
    $usr->idMesaEstado = $idMesaEstado;
    $usr->codigo = Mesa::GenerarCodigoAlfanumerico();
    $usr->descripcion = $descripcion;
    $usr->save();
    
    $payload = json_encode(array("mensaje" => "Mesa creada con exito"));
    
    $response->getBody()->write($payload);
    return $response
    ->withHeader('Content-Type', 'application/json');
  }

  public function Update($request, $response, $args)
  {
    $parametros = $request->getParsedBody();

    $idMesaEstado = isset($parametros['idMesaEstado']) ? $parametros['idMesaEstado'] : null;
    $descripcion = isset($parametros['descripcion']) ? $parametros['descripcion'] : null;
    
    // Conseguimos el objeto
    $usr = Mesa::where('id', '=', $args['id'])->first();
    
    // Si existe
    if ($usr !== null) {
      if($idMesaEstado !== null){ $usr->idMesaEstado = $idMesaEstado; }
      $usr->descripcion = $descripcion;

      $usr->save();
      $payload = json_encode(array("mensaje" => "Mesa modificada con exito"));
    } 
    else {
      $payload = json_encode(array("mensaje" => "Mesa no encontrada"));
    }

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function Delete($request, $response, $args)
  {
    $id = $args['id'];
    
    // Buscamos el mesa
    $obj = Mesa::find($id);

    // Borramos
    $obj->delete();

    $payload = json_encode(array("mensaje" => "Mesa borrada con exito"));

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }
}
