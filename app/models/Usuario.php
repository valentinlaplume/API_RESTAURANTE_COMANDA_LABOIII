<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Usuario extends Model
{
    use SoftDeletes;

    protected $table = 'usuario';
    protected $primaryKey = 'id';
    public $incrementing = true;
    //public $timestamps = false;

    const CREATED_AT = 'fechaAlta';
    const DELETED_AT = 'fechaBaja';
    const UPDATED_AT = 'fechaModificacion';

    protected $fillable = [
        'idUsuarioTipo', 'idArea', 
        'usuario', 'clave', 
        'estado', 
        'fechaAlta', 'fechaModificacion', 'fechaBaja'
    ];
    
    static public function ExisteUsuario($nombreUsuario)
    {
      if(Usuario::where('usuario', $nombreUsuario)->first() != null) { return true; }

      return false;
    }

    public function UsuarioTipo()
    {
        return $this->belongsTo(UsuarioTipo::class, 'idUsuarioTipo');
    }

    public function Area()
    {
        return $this->belongsTo(Area::class, 'idArea');
    }

    public function PrintUsuario()
    {
        echo '-------------------------';
        echo PHP_EOL;
        echo 'USUARIO: '. $this->usuario;
        echo PHP_EOL;
        echo 'TIPO: '. $this->UsuarioTipo->tipo;
        echo PHP_EOL;
        echo '-------------------------';
        echo PHP_EOL;
        echo PHP_EOL;
    }

}