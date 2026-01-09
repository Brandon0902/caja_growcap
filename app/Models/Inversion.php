<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Inversion extends Model
{
    use HasFactory;

    protected $table = 'inversiones';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'periodo',
        'monto_minimo',
        'monto_maximo',
        'rendimiento',
        'fecha',
        'fecha_edit',
        'id_usuario',
        'status',
    ];

    protected $casts = [
        'fecha'      => 'datetime',  // tu columna es datetime
        'fecha_edit' => 'datetime',
        'status'     => 'string',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
