<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Nuevo depósito pendiente</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f6f7fb;margin:0;padding:24px;">
  @php
    $clienteNombre = $cliente->nombre
      ?? $cliente->full_name
      ?? $cliente->name
      ?? ('Cliente #'.($cliente->id ?? '—'));

    $clienteEmail = $cliente->email ?? 'No registrado';

    $monto = (float)($deposito->cantidad ?? 0);

    $fechaTxt = $deposito->fecha_deposito ?? null;
    try {
      $fechaTxt = $fechaTxt ? \Carbon\Carbon::parse($fechaTxt)->format('Y-m-d H:i') : '—';
    } catch (\Throwable $e) {
      $fechaTxt = (string)$fechaTxt;
    }

    $cajaNombre = $deposito->caja->nombre ?? null;
    $cajaId     = $deposito->caja->id_caja ?? null;
  @endphp

  <div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:12px;padding:20px;border:1px solid #e7e9f2;">
    <h2 style="margin:0 0 12px 0;color:#111827;">Nuevo depósito pendiente</h2>

    <p style="margin:0 0 14px 0;color:#374151;line-height:1.5;">
      Se registró un depósito en estado <b>PENDIENTE</b>. Revisa y apruébalo desde el panel de administración.
    </p>

    <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:10px;padding:14px;margin:14px 0;">
      <p style="margin:6px 0;color:#111827;">
        <b>Cliente:</b> {{ $clienteNombre }}
      </p>

      <p style="margin:6px 0;color:#111827;">
        <b>Email del cliente:</b> {{ $clienteEmail }}
      </p>

      <hr style="border:none;border-top:1px solid #e5e7eb;margin:12px 0;">

      <p style="margin:6px 0;color:#111827;">
        <b>ID depósito:</b> #{{ $deposito->id }}
      </p>

      <p style="margin:6px 0;color:#111827;">
        <b>Monto:</b> ${{ number_format($monto, 2) }}
      </p>

      <p style="margin:6px 0;color:#111827;">
        <b>Fecha del depósito:</b> {{ $fechaTxt }}
      </p>

      @if($cajaNombre)
        <p style="margin:6px 0;color:#111827;">
          <b>Caja:</b> {{ $cajaNombre }}@if($cajaId) (ID {{ $cajaId }})@endif
        </p>
      @endif

      @if(!empty($deposito->nota))
        <p style="margin:6px 0;color:#111827;">
          <b>Nota del cliente:</b> {{ $deposito->nota }}
        </p>
      @endif

      @if(!empty($archivoUrl))
        <p style="margin:10px 0 0 0;color:#111827;">
          <b>Comprobante:</b>
          <a href="{{ $archivoUrl }}" target="_blank" rel="noopener noreferrer"
             style="color:#2563eb;text-decoration:underline;">
            Ver archivo
          </a>
        </p>
      @else
        <p style="margin:10px 0 0 0;color:#6b7280;font-size:13px;">
          <b>Comprobante:</b> No adjunto
        </p>
      @endif
    </div>

    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px;margin:14px 0;color:#7c2d12;">
      Acción requerida: revisa el depósito y apruébalo/recházalo en el panel.
    </div>

    <p style="margin:18px 0 0 0;color:#9ca3af;font-size:12px;">
      Este correo fue generado automáticamente por el sistema Growcap.
    </p>
  </div>
</body>
</html>
