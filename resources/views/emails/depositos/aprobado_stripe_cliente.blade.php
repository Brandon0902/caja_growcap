<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Depósito aprobado</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  @php
    $clienteNombre = $cliente->nombre
      ?? $cliente->full_name
      ?? $cliente->name
      ?? 'Cliente';

    $monto = (float)($deposito->cantidad ?? 0);

    $fechaTxt = $deposito->fecha_pago ?? $deposito->fecha_deposito ?? null;
    try {
      $fechaTxt = $fechaTxt ? \Carbon\Carbon::parse($fechaTxt)->format('Y-m-d H:i') : '—';
    } catch (\Throwable $e) {
      $fechaTxt = (string)$fechaTxt;
    }

    $cajaNombre = $deposito->caja->nombre ?? null;
  @endphp

  <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">Tu depósito fue aprobado</h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      Hola <b>{{ $clienteNombre }}</b>, tu depósito pagado con tarjeta mediante <b>Stripe</b> fue aprobado correctamente por un administrador.
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;">
        <b>Monto:</b> ${{ number_format($monto, 2) }}
      </p>

      <p style="margin:6px 0;color:#111827;">
        <b>Fecha:</b> {{ $fechaTxt }}
      </p>

      @if($cajaNombre)
        <p style="margin:6px 0;color:#111827;">
          <b>Caja:</b> {{ $cajaNombre }}
        </p>
      @endif
    </div>

    <div style="background:#ecfeff;border:1px solid #a5f3fc;border-radius:10px;padding:12px;margin:14px 0;color:#155e75;">
      Tu pago ya estaba confirmado en Stripe; este correo confirma la aprobación administrativa en el sistema.
    </div>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente por el sistema Growcap.
    </p>
  </div>
</body>
</html>
