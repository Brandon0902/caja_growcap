<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pago de depósito recibido</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  @php
    $clienteNombre = $cliente->nombre ?? $cliente->full_name ?? $cliente->name ?? 'Cliente';
    $monto = (float)($deposito->cantidad ?? 0);

    $fecha = $deposito->fecha_deposito ?? null;
    try {
      $fechaFmt = $fecha ? \Carbon\Carbon::parse($fecha)->format('Y-m-d H:i') : '—';
    } catch (\Throwable $e) {
      $fechaFmt = (string)$fecha;
    }
  @endphp

  <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">Pago realizado correctamente</h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      Hola <b>{{ $clienteNombre }}</b>, el pago de tu depósito se realizó correctamente en Stripe y será revisado por un administrador lo antes posible.
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;"><b>Monto:</b> ${{ number_format($monto, 2) }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Fecha depósito:</b> {{ $fechaFmt }}</p>
    </div>

    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px;margin:14px 0;color:#7c2d12;">
      Te notificaremos por correo cuando sea revisado.
    </div>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente por el sistema Growcap.
    </p>
  </div>
</body>
</html>
