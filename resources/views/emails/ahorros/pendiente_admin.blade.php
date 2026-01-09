<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Nuevo ahorro pendiente</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
@php
  $clienteNombre = $clienteData['nombre_completo'] ?? 'Cliente';
  $clienteEmail  = $clienteData['email'] ?? '—';

  $plan = $ahorro->relationLoaded('ahorro') ? $ahorro->ahorro : null;
  $planNombre = $plan?->nombre ?: ($plan?->tipo_ahorro ?: 'Plan de ahorro');

  $monto = (float)($ahorro->monto_ahorro ?? 0);
  $cuota = (float)($ahorro->cuota ?? 0);
  $freq  = (string)($ahorro->frecuencia_pago ?? 'Mensual');

  $fi = $ahorro->fecha_inicio ?? null;
  $ff = $ahorro->fecha_fin ?? null;

  try { $fiFmt = $fi ? \Carbon\Carbon::parse($fi)->format('Y-m-d H:i') : '—'; } catch (\Throwable $e) { $fiFmt = (string)$fi; }
  try { $ffFmt = $ff ? \Carbon\Carbon::parse($ff)->format('Y-m-d') : '—'; } catch (\Throwable $e) { $ffFmt = (string)$ff; }
@endphp

  <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">Nuevo ahorro pendiente</h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      Se creó un ahorro en <b>Pendiente</b>. Favor de atenderlo lo antes posible.
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;"><b>Ahorro ID:</b> {{ $ahorro->id }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Cliente:</b> {{ $clienteNombre }} <span style="color:#6b7280;">({{ $clienteEmail }})</span></p>
      <p style="margin:6px 0;color:#111827;"><b>Plan:</b> {{ $planNombre }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Monto inicial:</b> ${{ number_format($monto, 2) }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Cuota:</b> ${{ number_format($cuota, 2) }} <span style="color:#6b7280;">({{ $freq }})</span></p>
      <p style="margin:6px 0;color:#111827;"><b>Inicio:</b> {{ $fiFmt }} <span style="color:#6b7280;">·</span> <b>Fin:</b> {{ $ffFmt }}</p>
    </div>

    <div style="background:#eef2ff;border:1px solid #c7d2fe;border-radius:10px;padding:12px;margin:14px 0;color:#1e3a8a;">
      Revisa este ahorro en el panel de administración.
    </div>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente por el sistema Growcap.
    </p>
  </div>
</body>
</html>
