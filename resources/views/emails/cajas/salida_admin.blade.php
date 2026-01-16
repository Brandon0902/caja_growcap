<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Salida de dinero</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  @php
    $m = $movimiento;

    $actorNombre = (string)($actor->name ?? 'Usuario');
    $actorEmail  = (string)($actor->email ?? '—');

    $cajaNombre = $m->caja?->nombre ?? '—';
    $sucId = $m->caja?->id_sucursal ?? $m->id_sucursal ?? null;

    $monto = (float)($m->monto ?? 0);

    $fechaRaw = $m->fecha ?? null;
    try { $fechaFmt = $fechaRaw ? \Carbon\Carbon::parse($fechaRaw)->format('Y-m-d H:i') : '—'; }
    catch (\Throwable $e) { $fechaFmt = (string)$fechaRaw; }

    $desc = trim((string)($m->descripcion ?? ''));
    $desc = $desc !== '' ? $desc : '—';

    $montoAnterior = $m->monto_anterior !== null ? (float)$m->monto_anterior : null;
    $montoPosterior = $m->monto_posterior !== null ? (float)$m->monto_posterior : null;

    $catGas = $m->categoriaGasto?->nombre ?? null;
    $subGas = $m->subcategoriaGasto?->nombre ?? null;

    $proveedor = $m->proveedor?->nombre ?? null;
    $origenId = $m->origen_id ?? null;

    $accionTxt = $accion === 'actualizado' ? 'actualizó' : 'registró';
  @endphp

  <div style="max-width:760px;margin:0 auto;background:#fff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">Salida de dinero</h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      El usuario <b>{{ $actorNombre }}</b> ({{ $actorEmail }}) {{ $accionTxt }} una salida de dinero.
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;"><b>Caja:</b> {{ $cajaNombre }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Sucursal (id):</b> {{ $sucId ?? '—' }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Fecha:</b> {{ $fechaFmt }}</p>

      <hr style="border:none;border-top:1px solid #e5e7eb;margin:12px 0;">

      <p style="margin:6px 0;color:#111827;"><b>Monto:</b> ${{ number_format($monto, 2) }}</p>

      @if($montoAnterior !== null)
        <p style="margin:6px 0;color:#111827;"><b>Saldo anterior:</b> ${{ number_format($montoAnterior, 2) }}</p>
      @endif
      @if($montoPosterior !== null)
        <p style="margin:6px 0;color:#111827;"><b>Saldo posterior:</b> ${{ number_format($montoPosterior, 2) }}</p>
      @endif

      <p style="margin:10px 0 0 0;color:#111827;"><b>Descripción:</b> {{ $desc }}</p>

      @if($origenId)
        <p style="margin:10px 0 0 0;color:#111827;"><b>Origen ID:</b> {{ $origenId }}</p>
      @endif

      @if($proveedor)
        <p style="margin:10px 0 0 0;color:#111827;"><b>Proveedor:</b> {{ $proveedor }}</p>
      @endif

      @if($catGas || $subGas)
        <p style="margin:10px 0 0 0;color:#111827;">
          <b>Categoría gasto:</b> {{ $catGas ?? '—' }} @if($subGas) / {{ $subGas }} @endif
        </p>
      @endif
    </div>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente por el sistema {{ config('app.name', 'Growcap') }}.
    </p>
  </div>
</body>
</html>
