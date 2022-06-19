<?php
date_default_timezone_set("America/Buenos_Aires");
require_once './models/Producto.php';
require_once './models/ProductoTipo.php';
require_once './models/UsuarioAccionTipo.php';

require_once './interfaces/IApiUsable.php';

use \App\Models\Producto as Producto;
use \App\Models\ProductoTipo as ProductoTipo;
use \App\Models\UsuarioAccionTipo as UsuarioAccionTipo;
use Illuminate\Database\Capsule\Manager as DB;
class ProductoController implements IApiUsable
{
  public function GetAll($request, $response, $args)
  {
    $lista = Producto::all();
    $payload = json_encode(array("listaProducto" => $lista));

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function GetAllBy($request, $response, $args)
  {
    $field = $args['field'];
    $value = $args['value'];

    $lista = Producto::where($field, $value)->get();

    $payload = json_encode(array("listaProducto" => $lista));

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function GetFirstBy($request, $response, $args)
  {
    $field = $args['field'];
    $value = $args['value'];

    $obj = Producto::where($field, $value)->first();

    $payload = json_encode($obj);

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function Save($request, $response, $args)
  {
    $data = $request->getParsedBody();

    $idArea = isset($data['idArea']) ? $data['idArea'] : null;
    $idProductoTipo = isset($data['idProductoTipo']) ? $data['idProductoTipo'] : null;
    $nombre = isset($data['nombre']) ? $data['nombre'] : null;
    $precio = isset($data['precio']) ? $data['precio'] : null;
    $stock = isset($data['stock']) ? $data['stock'] : null;

    $obj = new Producto();
    if($idArea !== null) { $obj->idArea = $idArea; }
    if($idProductoTipo !== null) { $obj->idProductoTipo = $idProductoTipo; }
    if($nombre !== null) { $obj->nombre = $nombre; }
    if($precio !== null) { $obj->precio = $precio; }
    if($stock !== null) { $obj->stock = $stock; }
    $obj->save(); 

    $payload = json_encode(array("mensaje" => "Producto creado con exito"));
    $response->getBody()->write($payload);

    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function Update($request, $response, $args)
  {
    $data = $request->getParsedBody();

    $idArea = isset($data['idArea']) ? $data['idArea'] : null;
    $idProductoTipo = isset($data['idProductoTipo']) ? $data['idProductoTipo'] : null;
    $nombre = isset($data['nombre']) ? $data['nombre'] : null;
    $precio = isset($data['precio']) ? $data['precio'] : null;
    $stock = isset($data['stock']) ? $data['stock'] : null;

    // Conseguimos el objeto
    $obj = Producto::where('id', '=', $args['id'])->first();

    if ($obj !== null) {
      if($idArea !== null) { $obj->idArea = $idArea; }
      if($idProductoTipo !== null) { $obj->idProductoTipo = $idProductoTipo; }
      if($nombre !== null) { $obj->nombre = $nombre; }
      if($precio !== null) { $obj->precio = $precio; }
      if($stock !== null) { $obj->stock = $stock; }

      $obj->save();
      $payload = json_encode(array("mensaje" => "Producto modificado con exito"));
    } 
    else {
      $payload = json_encode(array("mensaje" => "Producto no encontrado"));
    }

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }

  public function Delete($request, $response, $args)
  {
    $obj = Producto::find($args['id']);
    if ($obj !== null) {
      $obj->producto = $objModificado;

      $producto->delete();
      $payload = json_encode(array("mensaje" => "Producto borrado con exito"));
    } 
    else {
      $payload = json_encode(array("mensaje" => "Producto no encontrado"));
    }

    $response->getBody()->write($payload);
    return $response
      ->withHeader('Content-Type', 'application/json');
  }
}
