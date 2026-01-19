{{-- emails/retiros/nueva_solicitud_cliente.blade.php --}}
<p>Hola {{ $cliente->nombre }} {{ $cliente->apellido }},</p>

<p>
  Hemos recibido tu <strong>solicitud de retiro de
  {{ $origen === 'ahorro' ? 'ahorro' : 'inversión' }}</strong>.
</p>

<p>
  <strong>Monto solicitado:</strong> ${{ number_format($retiro->cantidad, 2) }}<br>
  <strong>Fecha de solicitud:</strong> {{ $retiro->fecha_solicitud?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}
</p>

<p>
  Un administrador revisará tu solicitud y será atendida a la brevedad.
  Te notificaremos por correo cuando el estado cambie.
</p>

<p>Este mensaje es solo de confirmación, no es un comprobante de pago.</p>

<p>Saludos,<br>Growcap</p>
