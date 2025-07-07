<?php

namespace App\Models;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'documentos';
    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'documento_01',
        'documento_02',
        'documento_02_02',
        'documento_03',
        'documento_04',
        'documento_05',
        'id_usuario',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }
}
