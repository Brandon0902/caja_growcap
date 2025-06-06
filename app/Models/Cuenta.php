<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuenta extends Model
{
    use HasFactory;

    protected $table = 'cuentas';
    protected $primaryKey = 'id_cuenta';

    protected $fillable = [
        'codigo_cuenta',
        'nombre',
        'tipo',
        'balance_actual',
        'id_padre',
        'id_usuario',
    ];

    public function padre()
    {
        return $this->belongsTo(self::class, 'id_padre', 'id_cuenta');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function hijos()
    {
        return $this->hasMany(self::class, 'id_padre', 'id_cuenta');
    }
}
