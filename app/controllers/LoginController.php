<?php
 require_once './models/Usuario.php';
 use \App\Models\Usuario as Usuario;
 class LoginController 
 {

    private static function ValidarDatosLogin($usuario, $clave){
        if(!isset($usuario) || !isset($clave) 
        || $usuario == null || $clave == null
        || $usuario == "" || $clave == "") 
        { 
            throw new Exception("Usuario o Clave no definidos en Login."); 
        }
    }

    public function AccesApp($request, $response, $args)
    {
        try
        {
            $params = $request->getParsedBody();
            $usuarioNombre = $params['usuario'];
            $clave = $params['clave'];

            self::ValidarDatosLogin($usuarioNombre , $clave);
            
            $obj = Usuario::where('usuario', $usuarioNombre)->first();
            if($obj == null) { throw new Exception("No existe Usuario con ese nombre"); }
            
            if($obj->clave !== $clave) { throw new Exception("Clave incorrecta"); }
            
            if(!is_null($obj))
            {
                $datos = array('usuario' => $obj->usuario, 'clave' => $obj->clave);
                
                $token = AutentificadorJWT::CrearToken($datos);
                $payload = json_encode(array('jwt' => $token));

                $obj->PrintUsuario();

                // $idHistorialUsuario = Usuario::InsertarHistorialUsuario($obj);

                // if($idLoginInserted > 0){
                //     echo "Hola ".$obj->$usuario . " !!";
                // }
            }
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }catch (Exception $e) {
            $response = $response->withStatus(401);
            $payload = json_encode(array('error' => $e->getMessage()));
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }
        
    }
 }