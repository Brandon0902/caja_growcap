{{-- resources/views/emails/retiros/status_actualizado_cliente.blade.php --}}
@php
    use Carbon\Carbon;

    // fecha_solicitud probablemente viene como string (ej. "2025-12-10 20:00:00")
    $fechaSolicitud = $retiro->fecha_solicitud ?? null;
    $fechaFormateada = $fechaSolicitud
        ? Carbon::parse($fechaSolicitud)->format('d/m/Y H:i')
        : now()->format('d/m/Y H:i');

    // Mensaje genérico según el status_label
    $mensaje = match($status_label ?? 'Pendiente') {
        'Aprobado'  => 'Tu retiro ha sido aprobado. Pronto verás el movimiento reflejado.',
        'Pagado'    => 'Tu retiro ya fue pagado. Revisa el medio de pago asociado.',
        'Rechazado' => 'Tu retiro fue rechazado. Si tienes dudas, contáctanos para más detalles.',
        default     => 'Tu retiro ha cambiado de estado.'
    };
@endphp

<p>Hola {{ $cliente->nombre }} {{ $cliente->apellido }},</p>

<p>
  Tu
  <strong>{{ $origen === 'ahorro' ? 'retiro de ahorro' : 'retiro de inversión' }}</strong>
  ha cambiado de estado a:
  <strong>{{ $status_label }}</strong>.
</p>

<p>{{ $mensaje }}</p>

<p>
  <strong>Monto:</strong> ${{ number_format($retiro->cantidad, 2) }}<br>
  <strong>Fecha de solicitud:</strong> {{ $fechaFormateada }}
</p>

<p>Si tienes alguna duda, por favor contáctanos.</p>

<p>Saludos,<br>Growcap</p>
