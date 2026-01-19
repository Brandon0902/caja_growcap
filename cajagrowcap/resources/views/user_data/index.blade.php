<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-100 leading-tight">
      {{ __('Selección de Clientes para Datos') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

    @if(session('success'))
      <div class="mb-4 rounded-lg bg-green-100 p-4 text-green-800
                  dark:bg-green-900 dark:text-green-200">
        {{ session('success') }}
      </div>
    @endif

    <div class="mb-4">
      <form method="GET" action="{{ route('user_data.index') }}" class="flex w-full">
        <input
          name="search"
          value="{{ $search }}"
          placeholder="Buscar cliente…"
          class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600
                 rounded-l-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
        <button
          type="submit"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-r-md
                 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          Buscar
        </button>
      </form>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Cliente</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Inversiones</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Préstamos</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Ahorros</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Depósitos</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Saldo disp. general</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Estado de datos</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Acciones</th>
          </tr>
        </thead>

        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @foreach($clientes as $cliente)
            @php
              $saldoGeneral = (float)($cliente->sd_ahorros ?? 0)
                            + (float)($cliente->sd_inversiones ?? 0)
                            + (float)($cliente->sd_depositos ?? 0);
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-200">
                {{ $cliente->id }}
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-200">
                {{ $cliente->nombre }} {{ $cliente->apellido }}
              </td>

              {{-- Inversiones --}}
              <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-200">
                <div class="space-y-1">
                  <div><span class="font-semibold">Cantidad:</span> {{ number_format((int)$cliente->inv_count) }}</div>
                  <div><span class="font-semibold">Saldo:</span> ${{ number_format((float)$cliente->inv_saldo, 2) }}</div>
                </div>
              </td>

              {{-- Préstamos (solo informativo, NO entra al saldo general) --}}
              <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-200">
                <div class="space-y-1">
                  <div><span class="font-semibold">Cant:</span> {{ number_format((int)$cliente->pres_count) }}</div>
                  <div><span class="font-semibold">Saldo pendiente:</span> ${{ number_format((float)$cliente->pres_pend, 2) }}</div>
                  <div><span class="font-semibold">Saldo vencido:</span> ${{ number_format((float)$cliente->pres_venc, 2) }}</div>
                </div>
              </td>

              {{-- Ahorros --}}
              <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-200">
                <div class="space-y-1">
                  <div><span class="font-semibold">Ahorrando:</span> ${{ number_format((float)$cliente->ah_ahorrando, 2) }}</div>
                  <div><span class="font-semibold">Saldo acumulado:</span> ${{ number_format((float)$cliente->ah_acumulado, 2) }}</div>
                </div>
              </td>

              {{-- Depósitos (Total aprobados) --}}
              <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-200">
                <div class="space-y-1">
                  <div><span class="font-semibold">Total:</span> ${{ number_format((float)$cliente->dep_total, 2) }}</div>
                </div>
              </td>

              {{-- Saldo disponible general --}}
              <td class="px-6 py-4 whitespace-nowrap text-gray-800 dark:text-gray-100 font-semibold">
                ${{ number_format($saldoGeneral, 2) }}
              </td>

              <td class="px-6 py-4 whitespace-nowrap">
                @if($cliente->userData)
                  <span class="text-green-600 dark:text-green-400">Registrado</span>
                @else
                  <span class="text-gray-500 dark:text-gray-400">Sin datos</span>
                @endif
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-right">
                <a href="{{ route('clientes.datos.form', $cliente) }}"
                   class="inline-block px-3 py-1 rounded
                          {{ $cliente->userData ? 'bg-blue-500 hover:bg-blue-600' : 'bg-green-500 hover:bg-green-600' }}
                          text-white focus:outline-none focus:ring-2 focus:ring-offset-2
                          focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                  {{ $cliente->userData ? 'Ver / Editar' : 'Crear datos' }}
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="mt-4">
      {{ $clientes->links() }}
    </div>
  </div>
</x-app-layout>
