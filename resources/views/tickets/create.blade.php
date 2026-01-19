{{-- resources/views/tickets/create.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-start gap-3">
      <a href="{{ route('tickets.index') }}"
         class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300
                text-gray-800 text-sm font-medium rounded-md shadow">
        ← {{ __('Volver') }}
      </a>

      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Nuevo Ticket') }}
      </h2>
    </div>
  </x-slot>

  <style>[x-cloak]{display:none!important}</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

      {{-- Errores (descriptivos) --}}
      @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/30 p-4 text-red-800 dark:text-red-200 ring-1 ring-red-500/20">
          <div class="font-semibold mb-1">Whoops! Algo salió mal:</div>
          <ul class="list-disc pl-5 space-y-1 text-sm">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {{-- Área --}}
          <div>
            <label for="area" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Área <span class="text-red-500">*</span>
            </label>
            <input type="text" name="area" id="area"
                   value="{{ old('area') }}"
                   placeholder="Ej. Soporte, Sistemas, Cobranza…"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                          focus:border-purple-500 focus:ring-purple-500
                          dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
            @error('area')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          {{-- Cliente (opcional) --}}
          <div>
            <label for="id_cliente" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Cliente (opcional)
            </label>
            <select name="id_cliente" id="id_cliente"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                           focus:border-purple-500 focus:ring-purple-500
                           dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
              <option value="">{{ __('— Seleccionar cliente —') }}</option>
              @foreach($clientes as $c)
                <option value="{{ $c->id }}"
                  {{ (string)old('id_cliente') === (string)$c->id ? 'selected' : '' }}>
                  {{ $c->nombre }} {{ $c->email ? "({$c->email})" : '' }}
                </option>
              @endforeach
            </select>
            @error('id_cliente')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Si no eliges cliente, el ticket quedará asociado solo al usuario que lo creó.
            </p>
          </div>

          {{-- Asunto --}}
          <div class="lg:col-span-2">
            <label for="asunto" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Asunto <span class="text-red-500">*</span>
            </label>
            <input type="text" name="asunto" id="asunto"
                   value="{{ old('asunto') }}"
                   placeholder="Escribe un resumen corto del problema…"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                          focus:border-purple-500 focus:ring-purple-500
                          dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
            @error('asunto')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          {{-- Mensaje --}}
          <div class="lg:col-span-2">
            <label for="mensaje" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Mensaje <span class="text-red-500">*</span>
            </label>
            <textarea name="mensaje" id="mensaje" rows="5"
                      placeholder="Describe el problema con detalle…"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                             focus:border-purple-500 focus:ring-purple-500
                             dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">{{ old('mensaje') }}</textarea>
            @error('mensaje')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          {{-- Adjunto --}}
          <div>
            <label for="adjunto" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Adjunto (opcional)
            </label>
            <input type="file" name="adjunto" id="adjunto"
                   class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-200
                          file:mr-3 file:py-2 file:px-3 file:rounded-md
                          file:border-0 file:text-sm file:font-semibold
                          file:bg-purple-600 file:text-white hover:file:bg-purple-700">
            @error('adjunto')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Máximo 4MB.</p>
          </div>

          {{-- Estado --}}
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Estado <span class="text-red-500">*</span>
            </label>
            <select name="status" id="status"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                           focus:border-purple-500 focus:ring-purple-500
                           dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
              @php $st = (string)old('status','0'); @endphp
              <option value="0" {{ $st==='0' ? 'selected' : '' }}>Abierto</option>
              <option value="1" {{ $st==='1' ? 'selected' : '' }}>En progreso</option>
              <option value="2" {{ $st==='2' ? 'selected' : '' }}>Cerrado</option>
            </select>
            @error('status')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          {{-- Fechas --}}
          <div>
            <label for="fecha_seguimiento" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Fecha de seguimiento (opcional)
            </label>
            <input type="date" name="fecha_seguimiento" id="fecha_seguimiento"
                   value="{{ old('fecha_seguimiento') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                          focus:border-purple-500 focus:ring-purple-500
                          dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
            @error('fecha_seguimiento')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="fecha_cierre" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Fecha de cierre (opcional)
            </label>
            <input type="date" name="fecha_cierre" id="fecha_cierre"
                   value="{{ old('fecha_cierre') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                          focus:border-purple-500 focus:ring-purple-500
                          dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
            @error('fecha_cierre')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
          <a href="{{ route('tickets.index') }}"
             class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300 text-gray-800">
            Cancelar
          </a>
          <button type="submit"
                  class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 shadow">
            Guardar ticket
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
