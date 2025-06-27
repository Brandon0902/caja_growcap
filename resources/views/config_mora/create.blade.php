<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Nueva Configuración de Mora') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <form action="{{ route('config_mora.store') }}" method="POST">
        @csrf

        <div class="grid gap-6 sm:grid-cols-2">
          <div>
            <label for="cargo_fijo" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Cargo Fijo
            </label>
            <input
              type="number" step="0.01"
              name="cargo_fijo" id="cargo_fijo"
              value="{{ old('cargo_fijo') }}"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                     focus:border-purple-500 focus:ring-purple-500
                     dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
            />
            @error('cargo_fijo')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="porcentaje_mora" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              % Mora
            </label>
            <input
              type="number" step="0.01"
              name="porcentaje_mora" id="porcentaje_mora"
              value="{{ old('porcentaje_mora') }}"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                     focus:border-purple-500 focus:ring-purple-500
                     dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
            />
            @error('porcentaje_mora')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <div class="sm:col-span-2">
            <label for="periodo_gracia" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Período de Gracia (días)
            </label>
            <input
              type="number"
              name="periodo_gracia" id="periodo_gracia"
              value="{{ old('periodo_gracia') }}"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                     focus:border-purple-500 focus:ring-purple-500
                     dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
            />
            @error('periodo_gracia')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <div class="mt-6 flex justify-end space-x-2">
          <a
            href="{{ route('config_mora.index') }}"
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
            Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
