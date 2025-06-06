<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asiento extends Model
{
    use HasFactory;

    protected $table = 'asientos';
    protected $primaryKey = 'id_asiento';

    protected $fillable = [
        'id_sucursal',
        'fecha',
        'descripcion',
        'tipo',
        'id_usuario',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal', 'id_sucursal');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleAsiento::class, 'id_asiento', 'id_asiento');
    }
}
