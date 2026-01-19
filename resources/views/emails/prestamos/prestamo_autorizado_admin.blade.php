<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Préstamo autorizado</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">Préstamo autorizado</h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      La solicitud fue aprobada y el préstamo quedó en estado <b>AUTORIZADO</b>.
    </p>

    @php
      $cliente = $prestamo->cliente ?? null;
      $p       = $prestamo->plan ?? null;
      $caja    = $prestamo->caja ?? null;
      $inicio  = $prestamo->fecha_inicio ? \Carbon\Carbon::parse($prestamo->fecha_inicio)->format('d/m/Y') : '—';
    @endphp

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;"><b>ID:</b> #{{ $prestamo->id }}</p>

      <p style="margin:6px 0;color:#111827;">
        <b>Cliente:</b> {{ $cliente?->nombre }} {{ $cliente?->apellido }} ({{ $cliente?->email }})
      </p>

      <p style="margin:6px 0;color:#111827;">
        <b>Plan:</b> {{ $p?->periodo ?? 'Préstamo' }}
        @if($p && $p->interes !== null) — {{ $p->interes }}% @endif
      </p>

      <p style="margin:6px 0;color:#111827;"><b>Monto:</b> ${{ number_format((float)$prestamo->cantidad, 2) }}</p>
      @if($prestamo->semanas)
        <p style="margin:6px 0;color:#111827;"><b>Semanas:</b> {{ $prestamo->semanas }}</p>
      @endif
      <p style="margin:6px 0;color:#111827;"><b>Inicio:</b> {{ $inicio }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Caja:</b> {{ $caja?->nombre ?? ('#'.$prestamo->id_caja) }}</p>
    </div>

    <p style="margin:0 0 10px 0;color:#6b7280;font-size:13px;">
      Puedes revisar el registro aquí:
    </p>

    <a href="{{ url('/user-prestamos/'.$prestamo->id.'/edit') }}"
       style="display:inline-block;background:#4f46e5;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:bold;">
      Ver préstamo
    </a>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente.
    </p>
  </div>
</body>
</html>
