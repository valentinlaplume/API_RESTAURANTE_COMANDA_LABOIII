<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class Logger
{
    public static function LogOperacion($request, $response, $next)
    {
        $retorno = $next($request, $response);
        return $retorno;
    }

    public static function RegistrarLoginUsuario(Request $request, RequestHandler $handler)
    {
        echo 'antes'.PHP_EOL.PHP_EOL;
        $response = $handler->handle($request); 
        // var_dump($response);


        $contenidoApi = (string) $response->getBody();
        // echo($contenidoApi);


        if(isset($contenidoApi->jwt)) {
            echo 'Seteado!!!!!!!!!!!!!!!!!';
        }

        echo PHP_EOL.PHP_EOL.'despues'.PHP_EOL.PHP_EOL;

        return $response;
    }


}