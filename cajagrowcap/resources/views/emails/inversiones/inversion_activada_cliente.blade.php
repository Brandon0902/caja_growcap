<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Tu inversión fue activada</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">¡Tu inversión ya está activa!</h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      Ya revisamos tu pago con Stripe y tu inversión fue <b>ACTIVADA</b>.
    </p>

    @php
      $cliente = $inversion->cliente ?? null;
      $p       = $inversion->plan ?? null;

      // ===== Plan: "Inversión a X meses" =====
      $periodoRaw = $p ? trim((string)($p->periodo ?? '')) : '';
      $meses = null;

      if ($periodoRaw !== '') {
        if (is_numeric($periodoRaw)) {
          $meses = (int)$periodoRaw;
        } elseif (preg_match('/\d+/', $periodoRaw, $m)) {
          $meses = (int)$m[0];
        }
      }

      $planText = 'Inversión';
      if ($p) {
        if ($meses) {
          $planText = "Inversión a {$meses} meses";
        } elseif ($periodoRaw !== '') {
          $planText = "Inversión ({$periodoRaw})";
        }
      }

      $inicio  = $inversion->fecha_inicio ? \Carbon\Carbon::parse($inversion->fecha_inicio)->format('d/m/Y') : '—';
      $fin     = $inversion->fecha_fin ? \Carbon\Carbon::parse($inversion->fecha_fin)->format('d/m/Y') : '—';
    @endphp

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;"><b>Inversión:</b> #{{ $inversion->id }}</p>

      <p style="margin:6px 0;color:#111827;">
        <b>Plan:</b> {{ $planText }}
        @if($p && $p->rendimiento !== null) — {{ $p->rendimiento }}% @endif
      </p>

      <p style="margin:6px 0;color:#111827;"><b>Monto:</b> ${{ number_format((float)$inversion->inversion, 2) }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Inicio:</b> {{ $inicio }} &nbsp;&nbsp; <b>Fin:</b> {{ $fin }}</p>
    </div>

    <p style="margin:0;color:#6b7280;font-size:13px;">
      Gracias por invertir con nosotros.
    </p>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente.
    </p>
  </div>
</body>
</html>
