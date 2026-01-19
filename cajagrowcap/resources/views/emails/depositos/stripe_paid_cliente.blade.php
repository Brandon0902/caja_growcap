<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pago de depósito confirmado</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  @php
    $clienteNombre = $cliente->nombre
      ?? $cliente->full_name
      ?? $cliente->name
      ?? 'Cliente';
  @endphp

  <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">Pago de depósito confirmado</h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      Hola <b>{{ $clienteNombre }}</b>, el pago se realizó correctamente y será revisado por un administrador lo antes posible.
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;">
        <b>Depósito:</b> #{{ $deposito->id }}
      </p>

      <p style="margin:6px 0;color:#111827;">
        <b>Monto:</b> ${{ number_format((float)($deposito->cantidad ?? 0), 2) }}
      </p>

      <p style="margin:6px 0;color:#111827;">
        <b>Fecha:</b> {{ \Carbon\Carbon::parse($deposito->fecha_deposito)->format('Y-m-d') }}
      </p>
    </div>

    <div style="background:#ecfeff;border:1px solid #a5f3fc;border-radius:10px;padding:12px;margin:14px 0;color:#155e75;">
      En cuanto un admin lo apruebe, se generará el movimiento de caja correspondiente.
    </div>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente por el sistema Growcap.
    </p>
  </div>
</body>
</html>
