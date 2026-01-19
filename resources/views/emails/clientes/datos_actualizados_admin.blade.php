<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Datos actualizados</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">Datos del cliente actualizados</h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      Se actualizaron los datos del cliente en la sección <b>{{ $seccion ?: 'Datos del cliente' }}</b>.
      @if($actor === 'cliente')
        La actualización se realizó desde la app del cliente.
      @else
        La actualización se realizó desde el panel de administración.
      @endif
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;"><b>Cliente:</b> {{ $cliente->nombre }} {{ $cliente->apellido }}</p>
      <p style="margin:6px 0;color:#111827;"><b>Email:</b> {{ $cliente->email ?? 'No registrado' }}</p>
      <p style="margin:6px 0;color:#111827;"><b>ID:</b> #{{ $cliente->id }}</p>
    </div>

    <p style="margin:0 0 10px 0;color:#6b7280;font-size:13px;">
      Puedes revisar el registro aquí:
    </p>

    <a href="{{ route('clientes.datos.form', ['cliente' => $cliente->id, 'tab' => $tab ?? 'datos']) }}"
       style="display:inline-block;background:#4f46e5;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:bold;">
      Ver datos del cliente
    </a>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente.
    </p>
  </div>
</body>
</html>
