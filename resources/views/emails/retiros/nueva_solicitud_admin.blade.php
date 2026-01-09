{{-- emails/retiros/nueva_solicitud_admin.blade.php --}}
<p>Hola,</p>

<p>
  El cliente <strong>{{ $cliente->nombre }} {{ $cliente->apellido }}</strong>
  (ID: {{ $cliente->id }}, código: {{ $cliente->codigo_cliente }})
  ha realizado una <strong>nueva solicitud de retiro de
  {{ $origen === 'ahorro' ? 'ahorro' : 'inversión' }}</strong>.
</p>

<p>
  <strong>Monto:</strong> ${{ number_format($retiro->cantidad, 2) }}<br>
  <strong>Fecha de solicitud:</strong> {{ $retiro->fecha_solicitud?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}
</p>

<p>
  Por favor atienda esta solicitud lo antes posible desde el panel de administración.
</p>

<p>Saludos,<br>Growcap</p>
