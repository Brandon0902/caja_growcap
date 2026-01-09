<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Inversión pagada (Saldo)</title>
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
        $planText = "Inversión";
      }
    }

    $fin = $inversion->fecha_fin ?? ($inversion->fecha_fin_calc ?? null);

    if (!$fin && !empty($inversion->fecha_inicio) && $meses) {
      try {
        $fin = \Illuminate\Support\Carbon::parse($inversion->fecha_inicio)->addMonthsNoOverflow($meses);
      } catch (\Throwable $e) {}
    }

    $finFmt = null;
    if ($fin) {
      try { $finFmt = \Illuminate\Support\Carbon::parse($fin)->format('d/m/Y'); }
      catch (\Throwable $e) { $finFmt = (string)$fin; }
    }

    $inicioFmt = null;
    if (!empty($inversion->fecha_inicio)) {
      try { $inicioFmt = \Illuminate\Support\Carbon::parse($inversion->fecha_inicio)->format('d/m/Y'); }
      catch (\Throwable $e) { $inicioFmt = (string)$inversion->fecha_inicio; }
    }

    $clienteNombre = $cliente->nombre
      ?? $cliente->full_name
      ?? $cliente->name
      ?? ('Cliente #'.$cliente->id);

    $clienteEmail = $cliente->email ?? 'No registrado';

    $cajaNombre = $inversion->caja?->nombre ?? null;
    $cajaId     = $inversion->caja?->id_caja ?? ($inversion->id_caja ?? null);
  @endphp

  <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">Inversión pagada con saldo y activada</h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      Se confirmó el pago con <b>saldo disponible</b> y la inversión quedó en estado <b>ACTIVA</b>.
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;"><b>Cliente:</b> {{ $clienteNombre }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Email:</b> {{ $clienteEmail }}</p>

      <hr style="border:none;border-top:1px solid #e5e7eb;margin:12px 0;">

      <p style="margin:6px 0;color:#111827;"><b>ID inversión:</b> #{{ $inversion->id }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Monto:</b> ${{ number_format((float)$inversion->inversion, 2) }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Tasa:</b> {{ number_format((float)($inversion->rendimiento ?? 0), 2) }} %</p>

      @if($planText)
        <p style="margin:6px 0;color:#111827;"><b>Plan:</b> {{ $planText }}</p>
      @endif

      <p style="margin:6px 0;color:#111827;">
        <b>Inicio:</b> {{ $inicioFmt ?? '—' }}
        &nbsp;&nbsp;
        <b>Fin:</b> {{ $finFmt ?? '—' }}
      </p>

      <p style="margin:6px 0;color:#111827;"><b>Estado actual:</b> ACTIVA</p>
      <p style="margin:6px 0;color:#111827;"><b>Método:</b> Saldo</p>

      @if(!empty($inversion->fecha_fin))
        <p style="margin:6px 0;color:#6b7280;font-size:13px;">
          (BD) fecha_fin: {{ $inversion->fecha_fin }}
        </p>
      @endif

      @if($cajaId)
        <p style="margin:6px 0;color:#111827;">
          <b>Caja:</b> {{ $cajaNombre ?? ('Caja #'.$cajaId) }}
          @if($inversion->caja?->id_caja) (ID {{ $inversion->caja->id_caja }}) @endif
        </p>
      @endif
    </div>

    <h3 style="margin:14px 0 8px 0;color:#111827;font-size:16px;">Origen del saldo utilizado</h3>

    <div style="background:#ffffff;border:1px solid #eef2f7;border-radius:10px;padding:14px;">
      @if(!empty($origenes))
        <ul style="margin:0;padding-left:18px;color:#111827;">
          @foreach($origenes as $o)
            <li style="margin:6px 0;line-height:1.4;">
              <b>{{ $o['tipo'] ?? 'Origen' }}</b>
              @if(!empty($o['ref'])) — {{ $o['ref'] }} @endif
              @if(isset($o['monto'])) — ${{ number_format((float)$o['monto'], 2) }} @endif
              @if(!empty($o['fecha'])) — {{ $o['fecha'] }} @endif
            </li>
          @endforeach
        </ul>
      @else
        <p style="margin:0;color:#374151;">
          No fue posible detallar el origen (pero el pago sí se aplicó).
        </p>
      @endif
    </div>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente por el sistema Growcap.
    </p>
  </div>
</body>
</html>
