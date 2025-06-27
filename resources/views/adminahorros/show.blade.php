<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Ver Ahorro') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 space-y-4">

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Meses Mínimos</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $ahorro->meses_minimos }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Monto Mínimo</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $ahorro->monto_minimo }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Tipo de Ahorro</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $ahorro->tipo_ahorro }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Tasa Vigente (%)</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $ahorro->tasa_vigente }}%</p>
      </div>

      <div class="flex justify-end">
        <a href="{{ route('ahorros.index') }}"
           class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded">
          {{ __('Volver') }}
        </a>
      </div>
    </div>
  </div>
</x-app-layout>
