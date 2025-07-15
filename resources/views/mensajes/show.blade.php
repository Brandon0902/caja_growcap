<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      Mensaje #{{ $mensaje->id }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
      <div>
        <strong>Destinatario:</strong>
        {{ $mensaje->id_cliente
            ? $mensaje->cliente->nombre . ' ' . $mensaje->cliente->apellido
            : 'Todos los clientes' }}
      </div>
      <div>
        <strong>Tipo:</strong>
        @switch($mensaje->tipo)
          @case(1) Notificación @break
          @case(2) Recordatorio @break
          @case(3) Alerta @break
        @endswitch
      </div>
      <div>
        <strong>Asunto:</strong> {{ $mensaje->asunto }}
      </div>
      <div>
        <strong>Cuerpo:</strong><br>
        <div class="prose dark:prose-invert mt-2">
          {!! nl2br(e($mensaje->cuerpo)) !!}
        </div>
      </div>
      @if($mensaje->img)
        <div>
          <strong>Imagen adjunta:</strong><br>
          <img src="{{ route('mensajes.imagen',$mensaje) }}"
               class="mt-2 max-h-64 rounded border"/>
        </div>
      @endif
      <div>
        <strong>Fecha de envío:</strong>
        {{ optional($mensaje->fecha_envio)->format('Y-m-d H:i') ?: '—' }}
      </div>
      <div>
        <strong>Estado:</strong>
        @if($mensaje->status)
          Activo
        @else
          Inactivo
        @endif
      </div>

      <div class="pt-4">
        <a href="{{ route('mensajes.index') }}"
           class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 dark:text-gray-200 rounded-md">
          Volver al listado
        </a>
      </div>
    </div>
  </div>
</x-app-layout>
