<!doctype html>
<html lang="es">
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  @php
    $clienteNombre = $clienteData['nombre_completo'] ?? ('Cliente '.$clienteData['id'] ?? '');
    $email = $clienteData['email'] ?? '';
    $plan  = $planLabel ?: ($ahorro->ahorro->tipo_ahorro ?? 'Ahorro');
  @endphp

  <div style="max-width:720px;margin:0 auto;background:#fff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">Ahorro marcado como inactivo</h2>

    <p style="margin:0 0 12px 0;color:#374151;">
      Se marcó como <b>Inactivo</b> el ahorro <b>#{{ $ahorro->id }}</b> del cliente <b>{{ $clienteNombre }}</b> ({{ $email }}).
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;"><b>Plan:</b> {{ $plan }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Status:</b> Inactivo</p>
    </div>
  </div>
</body>
</html>
