@php
  $action = route('mensajes.update', $mensaje);
  $method = 'PATCH';
@endphp

<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      Editar Mensaje #{{ $mensaje->id }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <form action="{{ $action }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-6">
        @csrf @method($method)

        {{-- Destinatario --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Enviar a</label>
          <select name="id_cliente"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                         focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                         text-gray-900 dark:text-gray-100">
            <option value="" {{ $mensaje->id_cliente===null ? 'selected' : '' }}>Todos los clientes</option>
            @foreach($clientes as $c)
              <option value="{{ $c->id }}"
                {{ (string)old('id_cliente', $mensaje->id_cliente) === (string)$c->id ? 'selected' : '' }}>
                {{ $c->nombre }} {{ $c->apellido }}
              </option>
            @endforeach
          </select>
          @error('id_cliente') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        {{-- Tipo --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tipo de mensaje</label>
          <select name="tipo"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                         focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                         text-gray-900 dark:text-gray-100">
            <option value="1" {{ (string)old('tipo',$mensaje->tipo)==='1' ? 'selected' : '' }}>Notificación</option>
            <option value="2" {{ (string)old('tipo',$mensaje->tipo)==='2' ? 'selected' : '' }}>Recordatorio</option>
            <option value="3" {{ (string)old('tipo',$mensaje->tipo)==='3' ? 'selected' : '' }}>Alerta</option>
          </select>
          @error('tipo') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        {{-- Asunto (-> nombre) --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Asunto</label>
          <input type="text" name="asunto"
                 value="{{ old('asunto', $mensaje->nombre) }}"
                 class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                        focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                        text-gray-900 dark:text-gray-100">
          @error('asunto') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        {{-- Cuerpo (-> descripcion) --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Cuerpo</label>
          <textarea name="cuerpo" rows="4"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                           focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                           text-gray-900 dark:text-gray-100">{{ old('cuerpo', $mensaje->descripcion) }}</textarea>
          @error('cuerpo') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        {{-- Imagen --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Adjuntar imagen (opcional)</label>
          @if($mensaje->img)
            <div class="mb-2">
              {{-- Esta ruta debe existir: Route::get('/mensajes/{mensaje}/imagen',[...])->name('mensajes.imagen') --}}
              <img src="{{ route('mensajes.imagen', $mensaje) }}" class="h-24 rounded border" alt="Imagen del mensaje">
            </div>
          @endif
          <input type="file" name="img" class="mt-1 block w-full text-gray-700 dark:text-gray-200">
          @error('img') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        {{-- Fecha envío (-> fecha) --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Fecha de envío</label>
          <input type="datetime-local" name="fecha_envio"
                 value="{{ old('fecha_envio', $mensaje->fecha ? \Carbon\Carbon::parse($mensaje->fecha)->format('Y-m-d\TH:i') : '') }}"
                 class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                        focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                        text-gray-900 dark:text-gray-100">
          @error('fecha_envio') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        {{-- Estado --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Estado</label>
          <select name="status"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                         focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                         text-gray-900 dark:text-gray-100">
            <option value="1" {{ (string)old('status',$mensaje->status)==='1' ? 'selected' : '' }}>Activo</option>
            <option value="0" {{ (string)old('status',$mensaje->status)==='0' ? 'selected' : '' }}>Inactivo</option>
          </select>
          @error('status') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end space-x-2 pt-4">
          <a href="{{ route('mensajes.index') }}"
             class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 dark:text-gray-200 rounded-md">
            Cancelar
          </a>
          <button type="submit"
                  class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md">
            Actualizar Mensaje
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
