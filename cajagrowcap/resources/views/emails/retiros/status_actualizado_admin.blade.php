{{-- emails/retiros/status_actualizado_admin.blade.php --}}
@php
    use Carbon\Carbon;

    $fechaSolicitud = $retiro->fecha_solicitud ?? null;
    $fechaFormateada = $fechaSolicitud
        ? Carbon::parse($fechaSolicitud)->format('d/m/Y H:i')
        : now()->format('d/m/Y H:i');
@endphp

<p>Hola,</p>

<p>
  El cliente <strong>{{ $cliente->nombre }} {{ $cliente->apellido }}</strong>
  (ID: {{ $cliente->id }}, código: {{ $cliente->codigo_cliente }})
  tiene un <strong>retiro de {{ $origen === 'ahorro' ? 'ahorro' : 'inversión' }}</strong>
  actualizado.
</p>

<p>
  <strong>Retiro ID:</strong> #{{ $retiro->id }}<br>
  <strong>Estado:</strong> {{ $status_label }}<br>
  <strong>Monto:</strong> ${{ number_format($retiro->cantidad, 2) }}<br>
  <strong>Fecha de solicitud:</strong> {{ $fechaFormateada }}
</p>

<p>
  Puedes revisar el detalle en el panel de administración.
</p>

<p>Saludos,<br>Growcap</p>
