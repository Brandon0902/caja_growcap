<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Detalle de Pregunta') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
      <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">ID</h3>
        <p class="mt-1 text-gray-700 dark:text-gray-200">{{ $pregunta->id }}</p>
      </div>
      <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Pregunta</h3>
        <p class="mt-1 text-gray-700 dark:text-gray-200">{{ $pregunta->pregunta }}</p>
      </div>
      <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Respuesta</h3>
        <p class="mt-1 text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ $pregunta->respuesta }}</p>
      </div>
      <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Categoría</h3>
        <p class="mt-1 text-gray-700 dark:text-gray-200">{{ $pregunta->categoria }}</p>
      </div>
      @if($pregunta->img)
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Imagen</h3>
          <img src="{{ asset('storage/'.$pregunta->img) }}" class="mt-2 rounded max-h-64" alt="Imagen pregunta">
        </div>
      @endif
      <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Fecha</h3>
        <p class="mt-1 text-gray-700 dark:text-gray-200">
          {{ optional($pregunta->fecha)->format('d/m/Y H:i') }}
        </p>
      </div>
      <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Activo</h3>
        @if($pregunta->status == 1)
          <span class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold text-yellow-800
                       dark:bg-yellow-900 dark:text-yellow-200">Sí</span>
        @else
          <span class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold text-gray-800
                       dark:bg-gray-700 dark:text-gray-300">No</span>
        @endif
      </div>

      <div class="mt-6">
        <a href="{{ route('preguntas.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300
                  text-gray-700 rounded-md shadow-sm">
          Volver
        </a>
      </div>
    </div>
  </div>
</x-app-layout>
