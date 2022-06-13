<?php
error_reporting(-1);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../vendor/autoload.php';
require_once './middlewares/AutentificadorJWT.php';

require_once './controllers/UsuarioController.php';
require_once './controllers/UsuarioTipoController.php';
require_once './controllers/AreaController.php';
require_once './controllers/MesaController.php';
require_once './controllers/MesaEstadoController.php';
require_once './controllers/ProductoController.php';
require_once './controllers/PedidoController.php';

date_default_timezone_set('America/Argentina/Buenos_Aires');

// Load ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Instantiate App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$app->setBasePath('/slim-php-mysql-heroku/app');

// Eloquent
$container=$app->getContainer();

$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['MYSQL_HOST'],
    'database'  => $_ENV['MYSQL_DB'],
    'username'  => $_ENV['MYSQL_USER'],
    'password'  => $_ENV['MYSQL_PASS'],
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();



$app->get('[/]', function (Request $request, Response $response) {    
    $response->getBody()->write("TP COMANDA - LABO III - VALENTIN LAPLUME");
    return $response;
});

$app->group('/usuarios', function (RouteCollectorProxy $group) {
    $group->get('[/]', \UsuarioController::class . ':GetAll');
    $group->get('/{field}/{value}', \UsuarioController::class . ':GetAllBy'); 
    $group->get('/first/{field}/{value}', \UsuarioController::class . ':GetFirstBy'); 
    $group->post('[/]', \UsuarioController::class . ':Save');
    $group->put('/{id}', \UsuarioController::class . ':Update');
    $group->delete('/{id}', \UsuarioController::class . ':Delete');
});

$app->group('/usuarioTipos', function (RouteCollectorProxy $group) {
    $group->get('[/]', \UsuarioTipoController::class . ':GetAll');
    $group->get('/{field}/{value}', \UsuarioTipoController::class . ':GetAllBy'); 
    $group->get('/first/{field}/{value}', \UsuarioTipoController::class . ':GetFirstBy'); 
    // $group->post('[/]', \UsuarioTipoController::class . ':Save');
    // $group->put('/{id}', \UsuarioTipoController::class . ':Update');
    // $group->delete('/{id}', \UsuarioTipoController::class . ':Delete');
});

$app->group('/areas', function (RouteCollectorProxy $group) {
    $group->get('[/]', \AreaController::class . ':GetAll');
    $group->get('/{field}/{value}', \AreaController::class . ':GetAllBy'); 
    $group->get('/first/{field}/{value}', \AreaController::class . ':GetFirstBy'); 
    // $group->post('[/]', \AreaController::class . ':Save');
    // $group->put('/{id}', \AreaController::class . ':Update');
    // $group->delete('/{id}', \AreaController::class . ':Delete');
});

$app->group('/mesas', function (RouteCollectorProxy $group) {
    $group->get('[/]', \MesaController::class . ':GetAll');
    $group->get('/{field}/{value}', \MesaController::class . ':GetAllBy'); 
    $group->get('/first/{field}/{value}', \MesaController::class . ':GetFirstBy'); 
    $group->post('[/]', \MesaController::class . ':Save');
    $group->put('/{id}', \MesaController::class . ':Update');
    $group->delete('/{id}', \MesaController::class . ':Delete');
});

$app->group('/mesaEstados', function (RouteCollectorProxy $group) {
    $group->get('[/]', \MesaEstadoController::class . ':GetAll');
    $group->get('/{field}/{value}', \MesaEstadoController::class . ':GetAllBy'); 
    $group->get('/first/{field}/{value}', \MesaEstadoController::class . ':GetFirstBy'); 
    // $group->post('[/]', \MesaEstadoController::class . ':Save');
    // $group->put('/{id}', \MesaEstadoController::class . ':Update');
    // $group->delete('/{id}', \MesaEstadoController::class . ':Delete');
});

$app->group('/productos', function (RouteCollectorProxy $group) {
    $group->get('[/]', \ProductoController::class . ':GetAll');
    $group->get('/{field}/{value}', \ProductoController::class . ':GetAllBy');
    $group->get('/first/{field}/{value}', \ProductoController::class . ':GetFirstBy'); 
    $group->post('[/]', \ProductoController::class . ':Save');
    $group->put('/{id}', \ProductoController::class . ':Update');
    $group->delete('/{id}', \ProductoController::class . ':Delete');
});

$app->group('/pedidos', function (RouteCollectorProxy $group) {
    $group->get('/seguimiento/{codigoPedido}/{codigoMesa}', \PedidoController::class . ':GetAllPedidoDetalleCliente');
    $group->get('[/]', \PedidoController::class . ':GetAll');
    $group->get('/{field}/{value}', \PedidoController::class . ':GetAllBy');
    $group->get('/first/{field}/{value}', \PedidoController::class . ':GetFirstBy'); 
    $group->post('[/]', \PedidoController::class . ':Save');
    $group->put('/{id}', \PedidoController::class . ':Update');
    $group->delete('/{id}', \PedidoController::class . ':Delete');
});


$app->run();

//https://laravel.com/docs/8.x/eloquent
