<?php

require_once './models/Usuario.php';
require_once './models/UsuarioTipo.php';
require_once './middlewares/AutentificadorJWT.php';

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

use \App\Models\Usuario as Usuario;
use \App\Models\UsuarioTipo as UsuarioTipo;
class Acceso
{
    public static function LogOperacion($request, $response, $next)
    {
        $retorno = $next($request, $response);
        return $retorno;
    }

    public function isAdmin($request, $handler)
    {
        try
        {
            $header = $request->getHeaderLine('Authorization');
            $response = new Response();
            
            if(empty($header)) { throw new Exception('Es necesario Token para acceso'); }
            $token = trim(explode("Bearer", $header)[1]);

            AutentificadorJWT::VerificarToken($token);

            $data = AutentificadorJWT::ObtenerData($token);
            
            $obj = Usuario::where('usuario', $data->usuario)->first();
        
            if ($obj->idUsuarioTipo == UsuarioTipo::Administrador) {
                $obj->PrintUsuario();
                $response = $handler->handle($request); 
            } else {
                $response->getBody()->write(json_encode(array("error" => "Acceso solo Administradores")));
                $response = $response->withStatus(401);
            }
        }
        catch(Exception $e){
            $response->getBody()->write(json_encode(array("error" => $e->getMessage())));
            $response = $response->withStatus(401);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function isAdminOSocio($request, $handler)
    {
        try
        {
            $header = $request->getHeaderLine('Authorization');
            $response = new Response();
            
            if(empty($header)) { throw new Exception('Es necesario Token para acceso'); }
            $token = trim(explode("Bearer", $header)[1]);
            
            AutentificadorJWT::VerificarToken($token);

            $data = AutentificadorJWT::ObtenerData($token);
            
            $obj = Usuario::where('usuario', $data->usuario)->first();
        
            if ($obj->idUsuarioTipo == UsuarioTipo::Administrador ||
            $obj->idUsuarioTipo == UsuarioTipo::Socio) {
                $obj->PrintUsuario();
                $response = $handler->handle($request); 
            } else {
                $response->getBody()->write(json_encode(array("error" => "Acceso solo Administradores o Socios")));
                $response = $response->withStatus(401);
            }
        }
        catch(Exception $e){
            $response->getBody()->write(json_encode(array("error" => $e->getMessage())));
            $response = $response->withStatus(401);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function isMozo($request, $handler)
    {
        try
        {
            $header = $request->getHeaderLine('Authorization');
            $response = new Response();
            
            if(empty($header)) { throw new Exception('Es necesario Token para acceso'); }
            $token = trim(explode("Bearer", $header)[1]);

            AutentificadorJWT::VerificarToken($token);

            $data = AutentificadorJWT::ObtenerData($token);
            
            $obj = Usuario::where('usuario', $data->usuario)->first();
        
            if ($obj->idUsuarioTipo == UsuarioTipo::Mozo) {
                $obj->PrintUsuario();
                $response = $handler->handle($request); 
            } else {
                $response->getBody()->write(json_encode(array("error" => "Acceso solo Mozo")));
                $response = $response->withStatus(401);
            }
        }
        catch(Exception $e){
            $response->getBody()->write(json_encode(array("error" => $e->getMessage())));
            $response = $response->withStatus(401);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function isUsuario($request, $handler)
    {
        $header = $request->getHeaderLine('Authorization');
        $response = new Response();
        if (!empty($header)) {
            $token = trim(explode("Bearer", $header)[1]);
            AutentificadorJWT::VerificarToken($token);
            $data = AutentificadorJWT::ObtenerData($token);
            
            if(self::ValidarToken($token)){
                $obj = Usuario::where('usuario', $data->usuario)->first();
                $obj->PrintUsuario();
                $response = $handler->handle($request);
            }else{
                $response->getBody()->write(json_encode(array("error" => "Acceso solo Usuarios")));
                $response = $response->withStatus(401);
            }
        } else {
            $response->getBody()->write(json_encode(array("Token error" => "Es necesario Token para acceso")));
            $response = $response->withStatus(401);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    static private function ValidarToken($token)
    {
        $data = AutentificadorJWT::ObtenerData($token);
        if($data->usuario !== null && isset($data->usuario)){
            $obj = Usuario::where('usuario', $data->usuario)->first();
            
            if ($obj !== null) {
                return true;
            } 
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }


}