{{-- resources/views/tickets/show.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-white leading-tight">
        Ticket #{{ str_pad($ticket->id,3,'0',STR_PAD_LEFT) }} – {{ $ticket->asunto }}
      </h2>
      {{-- Botón Eliminar Ticket --}}
      <form action="{{ route('tickets.destroy', $ticket) }}"
            method="POST"
            onsubmit="return confirm('¿Estás seguro de eliminar este ticket?');"
      >
        @csrf
        @method('DELETE')
        <button type="submit"
                class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm">
          Eliminar Ticket
        </button>
      </form>
    </div>
  </x-slot>

  <div class="py-6 mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Detalles del Ticket --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <p class="text-gray-700 dark:text-gray-200"><strong>Área:</strong> {{ $ticket->area }}</p>
      <p class="text-gray-700 dark:text-gray-200">
        <strong>Cliente:</strong>
        {{ optional($ticket->cliente)->nombre ?? '—' }}
        {{ optional($ticket->cliente)->apellido ?? '' }}
      </p>
      <p class="mt-4 text-gray-700 dark:text-gray-200 whitespace-pre-line">
        {{ $ticket->mensaje }}
      </p>

      @if($ticket->adjunto)
        <a href="{{ route('tickets.download', $ticket) }}"
           class="mt-4 inline-block text-indigo-600 hover:underline">
          📎 Ver adjunto
        </a>
      @endif

      <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
        Creado: {{ \Carbon\Carbon::parse($ticket->fecha)->format('d/m/Y H:i') }}
      </p>
    </div>

    {{-- Lista de Respuestas --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Respuestas</h3>

      @forelse($ticket->respuestas as $resp)
        <div class="flex space-x-4">
          <img src="{{ asset('images/avatar_admin.png') }}"
               alt="Avatar Asesor"
               class="h-10 w-10 rounded-full flex-shrink-0">
          <div class="flex-1">
            <div class="flex justify-between items-center">
              <span class="font-medium text-gray-900 dark:text-gray-100">
                {{ optional($resp->usuario)->name ?? 'Usuario' }}
              </span>
              <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ \Carbon\Carbon::parse($resp->fecha)->format('d/m/Y H:i') }}
              </span>
            </div>
            <p class="mt-2 text-gray-700 dark:text-gray-200 whitespace-pre-line">
              {{ $resp->respuesta }}
            </p>
            {{-- Botón Eliminar Respuesta --}}
            <form action="{{ route('tickets.respuestas.destroy', $resp) }}"
                  method="POST"
                  class="mt-2 text-right"
                  onsubmit="return confirm('¿Eliminar esta respuesta?');"
            >
              @csrf
              @method('DELETE')
              <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                Eliminar respuesta
              </button>
            </form>
          </div>
        </div>
      @empty
        <p class="text-gray-500 dark:text-gray-400">Aún no hay respuestas para este ticket.</p>
      @endforelse
    </div>

    {{-- Formulario de Nueva Respuesta --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Agregar respuesta</h3>
      <form action="{{ route('tickets.respuestas.store', $ticket) }}"
            method="POST"
            class="mt-4 space-y-4"
      >
        @csrf

        {{-- 1) Select para cambiar estado --}}
        <div>
          <label for="status"
                 class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Cambiar estado
          </label>
          <select name="status" id="status" required
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                         focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-700
                         text-gray-900 dark:text-gray-100">
            @php
              $labels = [0=>'Pendiente',1=>'En Proceso',2=>'Cerrado'];
            @endphp
            @foreach($labels as $value => $label)
              <option value="{{ $value }}" {{ $ticket->status == $value ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- 2) Textarea de respuesta --}}
        <div>
          <label for="respuesta"
                 class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Tu respuesta
          </label>
          <textarea name="respuesta"
                    id="respuesta"
                    rows="4"
                    required
                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                           focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-700
                           text-gray-900 dark:text-gray-100"></textarea>
        </div>

        {{-- 3) Botón Enviar --}}
        <div class="text-right">
          <button type="submit"
                  class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
            Contestar
          </button>
        </div>
      </form>
    </div>

  </div>
</x-app-layout>
