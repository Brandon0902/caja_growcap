<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UserInversion extends Model
{
    protected $table = 'user_inversiones';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'inversion',
        'tipo',
        'tiempo',
        'rendimiento',
        'rendimiento_generado',
        'interes_acumulado',
        'retiros',
        'meses_minimos',
        'retiros_echos',
        'fecha_solicitud',
        'fecha_inicio',
        'fecha_fin',             // ✅ NUEVO: permitir guardar fecha_fin (BD)
        'fecha_alta',
        'fecha_edit',
        'deposito',
        'cantidad',
        'capital_actual',
        'fecha_ultimo_calculo',
        'id_usuario',
        'id_activo',
        'status',
        'id_caja',
        'nota',
        'fecha_respuesta',
    ];

    protected $casts = [
        'id_cliente'            => 'integer',

        // Montos
        'inversion'             => 'decimal:2',
        'cantidad'              => 'decimal:2',
        'capital_actual'        => 'decimal:2',
        'interes_acumulado'     => 'decimal:2',
        'rendimiento_generado'  => 'decimal:2',

        // Parámetros numéricos
        'tiempo'                => 'integer',
        'rendimiento'           => 'float',
        'retiros'               => 'integer',
        'retiros_echos'         => 'integer',
        'id_usuario'            => 'integer',
        'id_activo'             => 'integer',
        'status'                => 'integer',
        'id_caja'               => 'integer',

        // Fechas
        'fecha_solicitud'       => 'datetime',
        'fecha_inicio'          => 'date',
        'fecha_fin'             => 'date',     // ✅ NUEVO: cast para fecha_fin (BD)
        'fecha_alta'            => 'datetime',
        'fecha_edit'            => 'datetime',
        'fecha_respuesta'       => 'datetime',
        'fecha_ultimo_calculo'  => 'date',
    ];

    // ✅ Ya NO sobrescribimos fecha_fin (columna real).
    // Exponemos un "view" calculado para Blade/JSON.
    protected $appends = ['fecha_fin_calc_php', 'fecha_fin_view'];

    /* =========================
     * Relaciones
     * ========================= */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function plan()
    {
        // inversiones.id  ←→ user_inversiones.id_activo
        return $this->belongsTo(Inversion::class, 'id_activo', 'id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'id_caja', 'id_caja');
    }

    /* =========================
     * Atributos calculados
     * ========================= */

    /**
     * Fallback en PHP para fecha de fin con prioridad:
     *   periodo (plan) → tiempo (registro) → meses_minimos (plan)
     */
    public function getFechaFinCalcPhpAttribute()
    {
        if (!$this->fecha_inicio) return null;

        // 1) periodo (puede ser "12 meses" o "12")
        $pp = 0;
        if ($this->relationLoaded('plan') && $this->plan && !empty($this->plan->periodo)) {
            if (preg_match('/^\s*(\d+)/', (string) $this->plan->periodo, $m)) {
                $pp = (int) ($m[1] ?? 0);
            }
        }

        // 2) tiempo (en user_inversiones)
        $tt = (!empty($this->tiempo) && (int) $this->tiempo > 0) ? (int) $this->tiempo : 0;

        // 3) meses_minimos (en inversiones), puede ser varchar
        $mm = 0;
        if ($this->relationLoaded('plan') && $this->plan) {
            $mm = (int) ($this->plan->meses_minimos ?? 0);
        }

        $meses = $pp > 0 ? $pp : ($tt > 0 ? $tt : $mm);

        return $meses > 0
            ? Carbon::parse($this->fecha_inicio)->copy()->addMonths($meses)->toDateString()
            : null;
    }

    /**
     * ✅ Atributo para UI:
     * - Si en BD existe fecha_fin => esa manda
     * - Si no => usa el fallback PHP
     */
    public function getFechaFinViewAttribute()
    {
        // OJO: usamos getRawOriginal para asegurar que sea la columna real.
        $bd = $this->getRawOriginal('fecha_fin');
        $fin = $bd ?: $this->fecha_fin_calc_php;

        return $fin ? Carbon::parse($fin)->toDateString() : null;
    }
}
