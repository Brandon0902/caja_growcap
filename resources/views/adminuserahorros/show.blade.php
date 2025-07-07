<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __("Ahorros de") }} {{ $cliente->name }}
      </h2>
      <a href="{{ route('user_ahorros.index') }}"
         class="inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600
                text-white text-sm font-medium rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-gray-400">
        {{ __('← Volver') }}
      </a>
    </div>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
      <div class="overflow-x-auto">
        <table class="table-auto min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-purple-700 dark:bg-purple-900">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">#</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Tipo de Ahorro</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Monto</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha Inicio</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Saldo Disponible</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($ahorros as $ua)
              <tr>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ str_pad($ua->id, 3, '0', STR_PAD_LEFT) }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $ua->ahorro->tipo_ahorro }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ number_format($ua->monto_ahorro, 2) }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ \Carbon\Carbon::parse($ua->fecha_inicio)->format('Y-m-d') }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ number_format($ua->saldo_disponible, 2) }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5"
                    class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  {{ __('Este cliente no tiene ahorros.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $ahorros->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
