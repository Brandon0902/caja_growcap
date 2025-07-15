{{-- resources/views/adminuserahorros/show.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      Ahorros de {{ $cliente->nombre }} {{ $cliente->apellido }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{ showModal: false, modalMovs: [], modalAhorroLabel: '' }"
  >
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-purple-700 dark:bg-purple-900">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">#</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Tipo Ahorro</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Monto</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha Inicio</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Saldo Disponible</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Movimientos</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">

            @foreach($ahorros as $ahorro)
              @php
                $movs     = $ahorro->movimientos->map(fn($m) => [
                  'id'    => $m->id,
                  'monto' => number_format($m->monto,2),
                  'obs'   => $m->observaciones,
                  'saldo' => number_format($m->saldo_resultante,2),
                  'fecha' => $m->fecha,
                  'tipo'  => $m->tipo,
                ]);
                $label    = $ahorro->ahorro->tipo ?? $ahorro->tipo;
              @endphp

              <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ str_pad($ahorro->id, 3, '0', STR_PAD_LEFT) }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $label }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  ${{ number_format($ahorro->monto_ahorro, 2) }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $ahorro->fecha_inicio }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  ${{ number_format($ahorro->saldo_disponible, 2) }}
                </td>
                <td class="px-6 py-4 text-right">
                  <button
                    @click='
                      modalMovs = @json($movs);
                      modalAhorroLabel = @json("{$label} #{$ahorro->id}");
                      showModal = true;
                    '
                    class="inline-flex px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded-md"
                  >
                    Ver Movimientos
                  </button>
                </td>
              </tr>
            @endforeach

            @if($ahorros->isEmpty())
              <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  No hay ahorros registrados.
                </td>
              </tr>
            @endif

          </tbody>
        </table>
      </div>

      <div class="mt-4 text-right">
        {{ $ahorros->links() }}
      </div>
    </div>

    {{-- Modal de Movimientos --}}
    <div
      x-show="showModal"
      x-transition.opacity
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-2xl p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Movimientos de <span class="text-yellow-500" x-text="modalAhorroLabel"></span>
          </h3>
          <button @click="showModal = false"
                  class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
            &times;
          </button>
        </div>

        <div class="overflow-x-auto max-h-96">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
            <thead class="bg-gray-100 dark:bg-gray-700">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">ID</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Monto</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Obs.</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Saldo</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Fecha</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Tipo</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
              <template x-for="m in modalMovs" :key="m.id">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-200" x-text="m.id"></td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-200" x-text="`$${m.monto}`"></td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-200" x-text="m.obs"></td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-200" x-text="`$${m.saldo}`"></td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-200" x-text="m.fecha"></td>
                  <td class="px-4 py-2 text-gray-700 dark:text-gray-200" x-text="m.tipo"></td>
                </tr>
              </template>
              <template x-if="modalMovs.length === 0">
                <tr>
                  <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                    No hay movimientos para este ahorro.
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <div class="mt-4 text-right">
          <button @click="showModal = false"
                  class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
