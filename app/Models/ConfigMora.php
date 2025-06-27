<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigMora extends Model
{
    use HasFactory;

    protected $table = 'config_mora';
    protected $primaryKey = 'id';

    protected $fillable = [
        'cargo_fijo',
        'porcentaje_mora',
        'periodo_gracia',
    ];
}
