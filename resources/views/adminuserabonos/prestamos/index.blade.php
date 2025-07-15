<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Préstamos de :name', ['name' => $cliente->nombre . ' ' . $cliente->apellido]) }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 table-auto">
        <thead class="bg-green-600 dark:bg-green-800">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Período</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Monto</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Interés</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @foreach($prestamos as $p)
            <tr>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ str_pad($p->id, 3, '0', STR_PAD_LEFT) }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $p->tipo_prestamo }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                ${{ number_format($p->cantidad, 2) }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $p->interes }}%
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ \Carbon\Carbon::parse($p->fecha_solicitud)->format('Y-m-d') }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                @php
                  $labels = [
                    1 => 'Autorizado',
                    2 => 'Pendiente',
                    3 => 'En revisión',
                    4 => 'Rechazado',
                    5 => 'Pagado',
                    6 => 'Terminado',
                  ];
                @endphp
                {{ $labels[$p->status] ?? $p->status }}
              </td>
              <td class="px-6 py-4 text-right">
                <a
                  href="{{ route('adminuserabonos.abonos.index', $p->id) }}"
                  class="inline-flex items-center px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md transition"
                >
                  <svg xmlns="http://www.w3.org/2000/svg"
                       class="h-4 w-4 mr-1"
                       fill="none" viewBox="0 0 24 24"
                       stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5l7 7-7 7" />
                  </svg>
                  {{ __('Ver Detalles') }}
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $prestamos->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
