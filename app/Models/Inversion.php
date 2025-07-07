<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inversion extends Model
{
    use HasFactory;

    protected $table = 'inversiones';
    protected $primaryKey = 'id';

    public $timestamps = false; // o true si luego decides usar created_at/updated_at

    protected $fillable = [
        'periodo',
        'meses_minimos',
        'monto_minimo',
        'monto_maximo',
        'rendimiento',
        'fecha',
        'fecha_edit',
        'id_usuario',
        'status',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
