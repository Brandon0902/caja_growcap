<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Nuevo Ahorro') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <x-validation-errors class="mb-4"/>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <form action="{{ route('ahorros.store') }}" method="POST">
        @csrf

        {{-- Nombre --}}
        <div class="mb-4">
          <x-label for="nombre" value="Nombre" />
          <x-input id="nombre"
                   name="nombre"
                   type="text"
                   maxlength="255"
                   :value="old('nombre')"
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

        {{-- Categoría --}}
        <div class="mb-4">
          <x-label for="tipo_ahorro" value="Categoría" />
          <x-input id="tipo_ahorro"
                   name="tipo_ahorro"
                   type="text"
                   maxlength="50"
                   :value="old('tipo_ahorro')"
                   required />
        </div>

        {{-- Tasa vigente --}}
        <div class="mb-6">
          <x-label for="tasa_vigente" value="Tasa Vigente (%)" />
          <x-input id="tasa_vigente"
                   name="tasa_vigente"
                   type="number"
                   step="0.01"
                   min="0"
                   :value="old('tasa_vigente')"
                   required />
        </div>

        {{-- Botones --}}
        <div class="flex justify-end space-x-3">
          <a href="{{ route('ahorros.index') }}"
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
