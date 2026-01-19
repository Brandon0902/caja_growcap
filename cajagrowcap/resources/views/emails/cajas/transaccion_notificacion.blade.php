<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Notificación de transacción</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  @php
    $g = $gasto;

    $cajaOrigen  = $g->cajaOrigen ?? null;
    $cajaDestino = $g->cajaDestino ?? null;

    $sucursalOrigenNombre = optional($cajaOrigen?->sucursal)->nombre ?? '—';

    $tipo     = (string)($g->tipo ?? '—');
    $monto    = (float)($g->cantidad ?? 0);
    $concepto = trim((string)($g->concepto ?? ''));
    $concepto = $concepto !== '' ? $concepto : '—';

    $actorNombre = (string)($actor->name ?? 'Usuario');
    $actorEmail  = (string)($actor->email ?? '—');

    $fechaRaw = $accion === 'actualizada'
      ? ($g->updated_at ?? null)
      : ($g->created_at ?? null);

    try { $fechaFmt = $fechaRaw ? \Carbon\Carbon::parse($fechaRaw)->format('Y-m-d H:i') : '—'; }
    catch (\Throwable $e) { $fechaFmt = (string)$fechaRaw; }

    $accionTxt = match($accion) {
      'actualizada' => 'actualizada',
      'eliminada'   => 'eliminada',
      default       => 'registrada',
    };

    $comprobanteUrl = null;
    if (!empty($g->comprobante)) {
      try {
        $comprobanteUrl = route('gastos.comprobante', $g);
      } catch (\Throwable $e) {
        $comprobanteUrl = null;
      }
    }
  @endphp

  <div style="max-width:720px;margin:0 auto;background:#fff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">
      Transacción entre cajas {{ $accionTxt }}
    </h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      Se {{ $accionTxt }} una transacción que afecta el flujo de dinero.
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;"><b>Usuario:</b> {{ $actorNombre }} ({{ $actorEmail }})</p>
      <p style="margin:6px 0;color:#111827;"><b>Fecha:</b> {{ $fechaFmt }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Sucursal (origen):</b> {{ $sucursalOrigenNombre }}</p>

      <hr style="border:none;border-top:1px solid #e5e7eb;margin:12px 0;">

      <p style="margin:6px 0;color:#111827;"><b>Tipo:</b> {{ $tipo }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Monto:</b> ${{ number_format($monto, 2) }}</p>

      <p style="margin:6px 0;color:#111827;">
        <b>Caja origen:</b> {{ $cajaOrigen?->nombre ?? '—' }}
      </p>

      <p style="margin:6px 0;color:#111827;">
        <b>Caja destino:</b> {{ $cajaDestino?->nombre ?? '— (sin destino)' }}
      </p>

      <p style="margin:6px 0;color:#111827;"><b>Concepto:</b> {{ $concepto }}</p>

      @if($comprobanteUrl)
        <p style="margin:10px 0 0 0;color:#111827;">
          <b>Comprobante:</b>
          <a href="{{ $comprobanteUrl }}" style="color:#4f46e5;text-decoration:none;">Ver comprobante</a>
        </p>
      @endif
    </div>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente por el sistema Growcap.
    </p>
  </div>
</body>
</html>
