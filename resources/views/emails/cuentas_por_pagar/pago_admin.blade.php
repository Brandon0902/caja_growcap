<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pago de cuenta por pagar (admin)</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  @php
    $c = $cuenta;
    $d = $detalle;

    $idCuenta = $c->id_cuentas_por_pagar ?? $c->id ?? '—';
    $descCuenta = trim((string)($c->descripcion ?? ''));
    $descCuenta = $descCuenta !== '' ? $descCuenta : '—';

    $proveedor = $c->proveedor ?? null;
    $provNombre = $proveedor?->nombre ?? '—';

    $sucursal = $c->sucursal ?? null;
    $sucNombre = $sucursal?->nombre ?? '—';

    $cajaNombre = $caja?->nombre ?? '—';

    $actorNombre = (string)($actor->name ?? 'Usuario');
    $actorEmail  = (string)($actor->email ?? '—');

    $montoTotalCuenta = (float)($c->monto_total ?? 0);

    $numeroPago = (int)($d->numero_pago ?? 0);
    $estado     = (string)($d->estado ?? '—');
    $comentario = trim((string)($d->comentario ?? ''));
    $comentario = $comentario !== '' ? $comentario : '—';

    $saldoInicial = (float)($d->saldo_inicial ?? 0);
    $amortCap     = (float)($d->amortizacion_cap ?? 0);
    $pagoInteres  = (float)($d->pago_interes ?? 0);
    $montoPago    = (float)($d->monto_pago ?? 0);
    $saldoRest    = (float)($d->saldo_restante ?? 0);

    $fechaPagoRaw = $d->fecha_pago ?? null;
    try { $fechaPagoFmt = $fechaPagoRaw ? \Carbon\Carbon::parse($fechaPagoRaw)->format('Y-m-d') : '—'; }
    catch (\Throwable $e) { $fechaPagoFmt = (string)$fechaPagoRaw; }

    $accionTxt = $accion === 'actualizado' ? 'actualizó' : 'registró';
  @endphp

  <div style="max-width:760px;margin:0 auto;background:#fff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">Pago de cuenta por pagar (admin)</h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      El usuario <b>{{ $actorNombre }}</b> ({{ $actorEmail }}) {{ $accionTxt }} un pago de cuenta por pagar.
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;"><b>Sucursal:</b> {{ $sucNombre }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Proveedor:</b> {{ $provNombre }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Cuenta #:</b> {{ $idCuenta }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Descripción:</b> {{ $descCuenta }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Monto total cuenta:</b> ${{ number_format($montoTotalCuenta, 2) }}</p>

      <hr style="border:none;border-top:1px solid #e5e7eb;margin:12px 0;">

      <p style="margin:6px 0;color:#111827;"><b>Abono #:</b> {{ $numeroPago }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Fecha pago:</b> {{ $fechaPagoFmt }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Estado:</b> {{ $estado }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Caja:</b> {{ $cajaNombre }}</p>

      <hr style="border:none;border-top:1px solid #e5e7eb;margin:12px 0;">

      <p style="margin:6px 0;color:#111827;"><b>Saldo inicial:</b> ${{ number_format($saldoInicial, 2) }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Amortización capital:</b> ${{ number_format($amortCap, 2) }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Pago interés:</b> ${{ number_format($pagoInteres, 2) }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Monto pagado:</b> ${{ number_format($montoPago, 2) }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Saldo restante:</b> ${{ number_format($saldoRest, 2) }}</p>

      <p style="margin:10px 0 0 0;color:#111827;"><b>Comentario:</b> {{ $comentario }}</p>
    </div>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente por el sistema {{ config('app.name', 'Growcap') }}.
    </p>
  </div>
</body>
</html>
