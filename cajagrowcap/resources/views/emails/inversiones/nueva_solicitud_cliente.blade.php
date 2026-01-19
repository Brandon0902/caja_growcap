<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Solicitud de inversión recibida</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  @php
    $p = $inversion->plan ?? null;
    $periodoRaw = $p ? trim((string)($p->periodo ?? '')) : '';
    $meses = null;

    if ($periodoRaw !== '') {
      if (is_numeric($periodoRaw)) {
        $meses = (int)$periodoRaw;
      } elseif (preg_match('/\d+/', $periodoRaw, $m)) {
        $meses = (int)$m[0];
      }
    }

    $planText = null;
    if ($p) {
      if ($meses) {
        $planText = "Inversión a {$meses} meses";
      } elseif ($periodoRaw !== '') {
        $planText = "Inversión ({$periodoRaw})";
      } else {
        $planText = "Plan #".($p->id ?? $inversion->id_activo ?? '');
      }
    }

    $clienteNombre = $cliente->nombre
      ?? $cliente->full_name
      ?? $cliente->name
      ?? 'Cliente';
  @endphp

  <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">Hemos recibido tu solicitud de inversión</h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      Hola <b>{{ $clienteNombre }}</b>, tu solicitud fue registrada correctamente y será revisada por un administrador lo antes posible.
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;">
        <b>Monto:</b> ${{ number_format((float)($inversion->inversion ?? 0), 2) }}
      </p>

      <p style="margin:6px 0;color:#111827;">
        <b>Tasa:</b> {{ number_format((float)($inversion->rendimiento ?? 0), 2) }} %
      </p>

      @if($planText)
        <p style="margin:6px 0;color:#111827;"><b>Plan:</b> {{ $planText }}</p>
      @endif
    </div>

    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px;margin:14px 0;color:#7c2d12;">
      Te notificaremos por correo cuando sea revisada.
    </div>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente por el sistema Growcap.
    </p>
  </div>
</body>
</html>
