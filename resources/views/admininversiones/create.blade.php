<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Nueva Inversión') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    {{-- Errores de validación --}}
    <x-validation-errors class="mb-4"/>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <form action="{{ route('inversiones.store') }}" method="POST">
        @csrf

        {{-- Periodo --}}
        <div class="mb-4">
          <x-label for="periodo" value="Periodo" />
          <x-input id="periodo"
                   name="periodo"
                   type="text"
                   :value="old('periodo')"
                   required
                   autofocus />
        </div>

        {{-- Meses mínimos --}}
        <div class="mb-4">
          <x-label for="meses_minimos" value="Meses Mínimos" />
          <x-input id="meses_minimos"
                   name="meses_minimos"
                   type="number"
                   min="0"
                   :value="old('meses_minimos')"
                   required />
        </div>

        {{-- Monto mínimo --}}
        <div class="mb-4">
          <x-label for="monto_minimo" value="Monto Mínimo" />
          <x-input id="monto_minimo"
                   name="monto_minimo"
                   type="number"
                   step="0.01"
                   min="0"
                   :value="old('monto_minimo')"
                   required />
        </div>

        {{-- Monto máximo --}}
        <div class="mb-4">
          <x-label for="monto_maximo" value="Monto Máximo" />
          <x-input id="monto_maximo"
                   name="monto_maximo"
                   type="number"
                   step="0.01"
                   min="0"
                   :value="old('monto_maximo')"
                   required />
        </div>

        {{-- Rendimiento --}}
        <div class="mb-4">
          <x-label for="rendimiento" value="Rendimiento (%)" />
          <x-input id="rendimiento"
                   name="rendimiento"
                   type="number"
                   step="0.01"
                   min="0"
                   :value="old('rendimiento')"
                   required />
        </div>

        {{-- Fecha --}}
        <div class="mb-6">
          <x-label for="fecha" value="Fecha" />
          <x-input id="fecha"
                   name="fecha"
                   type="date"
                   :value="old('fecha')"
                   required />
        </div>

        {{-- Botones --}}
        <div class="flex justify-end space-x-3">
          <a href="{{ route('inversiones.index') }}"
             class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md
                    text-gray-700 bg-white hover:bg-gray-100 focus:outline-none
                    focus:ring-2 focus:ring-offset-2 focus:ring-gray-300
                    dark:border-gray-600 dark:text-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-500">
            {{ __('Cancelar') }}
          </a>

          <x-button type="submit"
                    class="bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600">
            {{ __('Guardar') }}
          </x-button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
