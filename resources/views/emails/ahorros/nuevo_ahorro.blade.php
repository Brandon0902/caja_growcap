<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo ahorro creado</title>
</head>
<body>
    <h2>Nuevo ahorro creado</h2>

    <p><strong>Cliente:</strong>
        {{ $cliente->nombre ?? $cliente->full_name ?? $cliente->name ?? ('Cliente #'.$cliente->id) }}
    </p>
    <p><strong>Email del cliente:</strong> {{ $cliente->email ?? 'No registrado' }}</p>

    <hr>

    <p><strong>ID ahorro:</strong> {{ $ahorro->id }}</p>
    <p><strong>Plan:</strong>
        @if($ahorro->ahorro)
            {{ $ahorro->ahorro->tipo_ahorro }}
        @else
            Plan #{{ $ahorro->ahorro_id }}
        @endif
    </p>

    <p><strong>Monto inicial:</strong> ${{ number_format($ahorro->monto_ahorro, 2) }}</p>
    <p><strong>Tasa anual:</strong> {{ $ahorro->rendimiento }} %</p>
    @if($ahorro->tiempo)
        <p><strong>Tiempo:</strong> {{ $ahorro->tiempo }} meses</p>
    @endif

    <p><strong>Fecha de inicio:</strong> {{ $ahorro->fecha_inicio }}</p>
    <p><strong>Frecuencia de pago:</strong> {{ $ahorro->frecuencia_pago }}</p>

    @if($ahorro->caja)
        <p><strong>Caja:</strong> {{ $ahorro->caja->nombre }} (ID {{ $ahorro->caja->id_caja }})</p>
    @endif

    <hr>

    <p>
        Este ahorro fue creado en estado <strong>ACTIVO</strong>.<br>
        Puedes revisar más detalles en el panel de administración de la Caja.
    </p>

    <p style="font-size: 12px; color: #666;">
        Correo generado automáticamente por el sistema Growcap.
    </p>
</body>
</html>
