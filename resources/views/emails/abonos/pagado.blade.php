<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Abono pagado</title>
</head>
<body>
    <h2>Abono pagado en préstamo</h2>

    <p><strong>Cliente:</strong>
        {{ $cliente->nombre ?? $cliente->full_name ?? $cliente->name ?? ('Cliente #'.$cliente->id) }}
    </p>
    <p><strong>Email del cliente:</strong> {{ $cliente->email ?? 'No registrado' }}</p>

    <hr>

    <p><strong>Préstamo ID:</strong> {{ $prestamo->id }}</p>
    <p><strong>Descripción préstamo:</strong>
        {{ $prestamo->descripcion ?? ("Préstamo #".$prestamo->id) }}
    </p>
    <p><strong>Monto original del préstamo:</strong>
        ${{ number_format((float)($prestamo->cantidad ?? 0), 2) }}
    </p>

    <p><strong>Abono ID:</strong> {{ $abono->id }}</p>
    <p><strong>Número de pago:</strong> {{ $abono->num_pago }}</p>
    <p><strong>Monto del pago recibido:</strong> ${{ number_format($monto, 2) }}</p>

    @if(!empty($breakdown))
        <h3>Resumen del préstamo después del pago</h3>
        <ul>
            <li><strong>Monto pagado total (prestamo):</strong>
                ${{ number_format((float)($breakdown['monto_pagado'] ?? 0), 2) }}
            </li>
            <li><strong>Saldo restante del préstamo:</strong>
                ${{ number_format((float)($breakdown['saldo_restante'] ?? 0), 2) }}
            </li>
            <li><strong>Saldo a favor del cliente:</strong>
                ${{ number_format((float)($breakdown['saldo_a_favor'] ?? 0), 2) }}
            </li>
        </ul>
    @endif

    <hr>

    <p>Este pago se registró a través del módulo de cliente (API de abonos).</p>

    <p style="font-size: 12px; color: #666;">
        Correo generado automáticamente por el sistema Growcap.
    </p>
</body>
</html>
