<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Editar Pregunta') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <form action="{{ route('preguntas.update', $pregunta) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid gap-6">
          {{-- Pregunta --}}
          <div>
            <label for="pregunta" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Pregunta
            </label>
            <textarea
              name="pregunta" id="pregunta" rows="2"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                     focus:border-purple-500 focus:ring-purple-500
                     dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
            >{{ old('pregunta', $pregunta->pregunta) }}</textarea>
            @error('pregunta')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          {{-- Respuesta --}}
          <div>
            <label for="respuesta" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Respuesta
            </label>
            <textarea
              name="respuesta" id="respuesta" rows="4"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                     focus:border-purple-500 focus:ring-purple-500
                     dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
            >{{ old('respuesta', $pregunta->respuesta) }}</textarea>
            @error('respuesta')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          {{-- Categoría --}}
          <div>
            <label for="categoria" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Categoría
            </label>
            <input
              type="text"
              name="categoria" id="categoria"
              value="{{ old('categoria', $pregunta->categoria) }}"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                     focus:border-purple-500 focus:ring-purple-500
                     dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
            />
            @error('categoria')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          {{-- Imagen --}}
          <div>
            <label for="img" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Imagen (si quieres reemplazar)
            </label>
            @if($pregunta->img)
              <img src="{{ asset('storage/'.$pregunta->img) }}"
                   class="h-24 mb-2 rounded" alt="preview">
            @endif
            <input
              type="file"
              name="img" id="img"
              accept="image/*"
              class="mt-1 block w-full text-gray-700
                     dark:bg-gray-700 dark:border-gray-600"
            />
            @error('img')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          {{-- Estado --}}
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Estado
            </label>
            <select
              name="status" id="status"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                     focus:border-purple-500 focus:ring-purple-500
                     dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
            >
              <option value="1" {{ old('status', $pregunta->status)=='1' ? 'selected':'' }}>
                Activo
              </option>
              <option value="0" {{ old('status', $pregunta->status)=='0' ? 'selected':'' }}>
                Inactivo
              </option>
            </select>
            @error('status')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <div class="mt-6 flex justify-end space-x-2">
          <a
            href="{{ route('preguntas.index') }}"
            class="inline-flex items-center px-4 py-2 border border-gray-300
                   text-gray-700 bg-white hover:bg-gray-50 rounded-md shadow-sm"
          >
            Cancelar
          </a>
          <button
            type="submit"
            class="inline-flex items-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600
                   text-white font-semibold rounded-md shadow-sm focus:outline-none
                   focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400"
          >
            Actualizar
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
