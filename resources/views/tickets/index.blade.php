<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Tickets de Soporte') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{ search: '{{ request('search','') }}' }"
  >
    {{-- Acción + Búsqueda --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
      <input type="text"
             x-model="search"
             @input.debounce.500ms="window.location = '{{ route('tickets.index') }}?search=' + search"
             placeholder="{{ __('Buscar…') }}"
             class="px-3 py-2 border rounded-md shadow-sm
                    focus:outline-none focus:ring-2 focus:ring-purple-500
                    bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                    dark:border-gray-600 w-full sm:w-1/3"/>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-indigo-600 dark:bg-indigo-800">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Cliente</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Asunto</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Área</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($tickets as $ticket)
              <tr class="hover:bg-gray-100 dark:hover:bg-gray-700"
                  x-show="$el.textContent.toLowerCase().includes(search.toLowerCase())"
              >
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ str_pad($ticket->id, 3, '0', STR_PAD_LEFT) }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $ticket->cliente->nombre ?? '—' }}
                  {{ $ticket->cliente->apellido ?? '' }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $ticket->asunto }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $ticket->area }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{-- Ahora fecha es Carbon --}}
                  {{ $ticket->fecha->format('d/m/Y') }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  @php
                    $labels = [0 => 'Pendiente', 1 => 'En Proceso', 2 => 'Cerrado'];
                    $colors = [
                      0 => 'bg-red-100 text-red-800',
                      1 => 'bg-yellow-100 text-yellow-800',
                      2 => 'bg-green-100 text-green-800',
                    ];
                  @endphp
                  <span class="px-2 py-1 rounded text-sm {{ $colors[$ticket->status] }}">
                    {{ $labels[$ticket->status] }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <a href="{{ route('tickets.show', $ticket) }}"
                     class="inline-flex items-center px-3 py-2 bg-indigo-500 hover:bg-indigo-600
                            text-white text-sm font-medium rounded-md shadow-sm">
                    Ver / Responder
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7"
                    class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  No hay tickets registrados.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $tickets->appends(['search' => request('search')])->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
