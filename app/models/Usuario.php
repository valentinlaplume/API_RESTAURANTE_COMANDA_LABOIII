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

    public function UsuarioTipo()
    {
        return $this->belongsTo(UsuarioTipo::class, 'idUsuarioTipo');
    }

    public function Area()
    {
        return $this->belongsTo(Area::class, 'idArea');
    }

}