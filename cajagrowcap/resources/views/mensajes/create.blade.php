<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      Nuevo Mensaje
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <form action="{{ route('mensajes.store') }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-6">
        @csrf

        {{-- Destinatario --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Enviar a
          </label>
          <select name="id_cliente"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                         focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                         text-gray-900 dark:text-gray-100">
            <option value="">Todos los clientes</option>
            @foreach($clientes as $c)
              <option value="{{ $c->id }}"
                {{ old('id_cliente') == $c->id ? 'selected' : '' }}>
                {{ $c->nombre }} {{ $c->apellido }}
              </option>
            @endforeach
          </select>
          @error('id_cliente')
            <p class="text-red-600 text-sm">{{ $message }}</p>
          @enderror
        </div>

        {{-- Tipo --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Tipo de mensaje
          </label>
          <select name="tipo"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                         focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                         text-gray-900 dark:text-gray-100">
            <option value="1" {{ old('tipo')=='1'? 'selected':'' }}>Notificación</option>
            <option value="2" {{ old('tipo')=='2'? 'selected':'' }}>Recordatorio</option>
            <option value="3" {{ old('tipo')=='3'? 'selected':'' }}>Alerta</option>
          </select>
          @error('tipo')
            <p class="text-red-600 text-sm">{{ $message }}</p>
          @enderror
        </div>

        {{-- Asunto --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Asunto</label>
          <input type="text"
                 name="asunto"
                 value="{{ old('asunto') }}"
                 class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                        focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                        text-gray-900 dark:text-gray-100"/>
          @error('asunto')
            <p class="text-red-600 text-sm">{{ $message }}</p>
          @enderror
        </div>

        {{-- Cuerpo --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Cuerpo</label>
          <textarea name="cuerpo" rows="4"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                           focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                           text-gray-900 dark:text-gray-100">{{ old('cuerpo') }}</textarea>
          @error('cuerpo')
            <p class="text-red-600 text-sm">{{ $message }}</p>
          @enderror
        </div>

        {{-- Imagen (opcional) --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Adjuntar imagen (png/jpg/pdf)
          </label>
          <input type="file" name="img"
                 class="mt-1 block w-full text-gray-700 dark:text-gray-200"/>
          @error('img')
            <p class="text-red-600 text-sm">{{ $message }}</p>
          @enderror
        </div>

        {{-- Fecha envío --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Fecha de envío
          </label>
          <input type="datetime-local"
                 name="fecha_envio"
                 value="{{ old('fecha_envio') }}"
                 class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                        focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                        text-gray-900 dark:text-gray-100"/>
          @error('fecha_envio')
            <p class="text-red-600 text-sm">{{ $message }}</p>
          @enderror
        </div>

        {{-- Estado --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Estado</label>
          <select name="status"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                         focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                         text-gray-900 dark:text-gray-100">
            <option value="1" {{ old('status','1')=='1'? 'selected':'' }}>Activo</option>
            <option value="0" {{ old('status')=='0'? 'selected':'' }}>Inactivo</option>
          </select>
          @error('status')
            <p class="text-red-600 text-sm">{{ $message }}</p>
          @enderror
        </div>

        <div class="flex justify-end space-x-2 pt-4">
          <a href="{{ route('mensajes.index') }}"
             class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 dark:text-gray-200 rounded-md">
            Cancelar
          </a>
          <button type="submit"
                  class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">
            Guardar Mensaje
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
