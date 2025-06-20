<?php

// app/Models/Prestamo.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prestamo extends Model
{
    use HasFactory;

    protected $table = 'prestamos';
    protected $primaryKey = 'id_prestamo';

    protected $fillable = [
        'id_usuario',
        'periodo',
        'semanas',
        'interes',
        'monto_minimo',
        'monto_maximo',
        'antiguedad',
        'status',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}

