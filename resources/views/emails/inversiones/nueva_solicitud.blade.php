<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Inversión</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  @php
    $status = (int)($inversion->status ?? 0);

    $statusLabel = match ($status) {
      1 => 'PENDIENTE',
      2 => 'ACTIVA',
      3 => 'TERMINADA',
      default => 'DESCONOCIDO',
    };

    $paymentMethod = $inversion->payment_method ?? null;   // 'saldo' | 'stripe' | null
    $paymentStatus = $inversion->payment_status ?? null;   // 'paid' | 'pending' | null

    $yaPagadaOActiva = ($status === 2) || ($paymentStatus === 'paid');

    // ---- Plan label basado en periodo ----
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
      ?? ('Cliente #'.$cliente->id);

    $clienteEmail = $cliente->email ?? 'No registrado';

    $metodoTxt = null;
    if (!empty($paymentMethod)) {
      $metodoTxt = $paymentMethod === 'saldo' ? 'Saldo' : 'Stripe';
    }

    $pagoTxt = null;
    if (!empty($paymentStatus)) {
      $pagoTxt = strtoupper((string)$paymentStatus);
    }
  @endphp

  <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">
      {{ $status === 1 ? 'Nueva solicitud de inversión' : 'Actualización de inversión' }}
    </h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      Se generó una notificación de inversión para el cliente <b>{{ $clienteNombre }}</b>.
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;"><b>Cliente:</b> {{ $clienteNombre }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Email del cliente:</b> {{ $clienteEmail }}</p>

      <hr style="border:none;border-top:1px solid #e5e7eb;margin:12px 0;">

      <p style="margin:6px 0;color:#111827;"><b>ID inversión:</b> #{{ $inversion->id }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Monto:</b> ${{ number_format((float)($inversion->inversion ?? 0), 2) }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Tasa:</b> {{ number_format((float)($inversion->rendimiento ?? 0), 2) }} %</p>

      @if($planText)
        <p style="margin:6px 0;color:#111827;"><b>Plan:</b> {{ $planText }}</p>
      @endif

      @if($inversion->tiempo)
        <p style="margin:6px 0;color:#111827;"><b>Tiempo:</b> {{ $inversion->tiempo }} meses</p>
      @endif

      <p style="margin:6px 0;color:#111827;"><b>Fecha de solicitud:</b> {{ $inversion->fecha_solicitud }}</p>

      @if($inversion->caja)
        <p style="margin:6px 0;color:#111827;"><b>Caja:</b> {{ $inversion->caja->nombre }} (ID {{ $inversion->caja->id_caja }})</p>
      @endif

      <p style="margin:6px 0;color:#111827;"><b>Estado actual:</b> <b>{{ $statusLabel }}</b></p>

      @if($metodoTxt)
        <p style="margin:6px 0;color:#111827;"><b>Método de pago:</b> {{ $metodoTxt }}</p>
      @endif

      @if($pagoTxt)
        <p style="margin:6px 0;color:#111827;"><b>Pago:</b> {{ $pagoTxt }}</p>
      @endif
    </div>

    @if(!$yaPagadaOActiva)
      <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px;margin:14px 0;color:#7c2d12;">
        Esta inversión se creó en estado <b>PENDIENTE</b>.<br>
        Por favor, ingresa al panel de administración para revisarla y aprobarla o rechazarla.
      </div>
    @else
      <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;padding:12px;margin:14px 0;color:#065f46;">
        Esta inversión ya quedó <b>{{ $statusLabel }}</b> (pago aplicado).<br>
        No requiere aprobación manual.
      </div>
    @endif

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente por el sistema Growcap.
    </p>
  </div>
</body>
</html>
