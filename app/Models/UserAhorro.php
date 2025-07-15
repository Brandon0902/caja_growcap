<?php

namespace App\Models;

// app/Models/UserAhorro.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAhorro extends Model
{
    use HasFactory;

    protected $table = 'user_ahorro';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        // ... tus otros campos ...
    ];

    // Relación al tipo de ahorro
    public function ahorro()
    {
        return $this->belongsTo(Ahorro::class, 'ahorro_id');
    }

    // **Nueva** relación a movimientos de este ahorro
    public function movimientos()
    {
        return $this->hasMany(MovimientoAhorro::class, 'id_ahorro');
    }
}
