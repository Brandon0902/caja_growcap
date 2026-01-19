<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Correo actualizado</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  @php
    $clienteNombre = trim(($cliente->nombre ?? '').' '.($cliente->apellido ?? '')) ?: 'Cliente';
    $nuevo = (string)($cliente->email ?? '');
    $anterior = (string)($oldEmail ?? '');
    $fechaFmt = \Carbon\Carbon::now()->format('Y-m-d H:i');
  @endphp

  <div style="max-width:720px;margin:0 auto;background:#fff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">Tu correo fue actualizado</h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      Hola <b>{{ $clienteNombre }}</b>, este correo es para informarte que tu email de acceso/contacto en Growcap fue actualizado.
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;"><b>Correo anterior:</b> {{ $anterior !== '' ? $anterior : '—' }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Correo nuevo:</b> {{ $nuevo !== '' ? $nuevo : '—' }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Fecha del cambio:</b> {{ $fechaFmt }}</p>
    </div>

    <p style="margin:14px 0 0 0;color:#374151;line-height:1.5;">
      Si tú no solicitaste este cambio, por favor contacta al administrador o soporte del sistema.
    </p>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente por el sistema Growcap.
    </p>
  </div>
</body>
</html>
