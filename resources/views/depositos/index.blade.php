{{-- resources/views/depositos/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Clientes Activos') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" 
       x-data="{ search: '{{ $search }}' }">
    {{-- Buscador --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center">
      <input type="text"
             x-model="search"
             @input.debounce.500ms="window.location = '{{ route('depositos.index') }}?search=' + search"
             placeholder="Buscar…"
             class="w-full sm:w-1/3 px-3 py-2 border rounded-md
                    bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200"/>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
        <thead class="bg-green-700 dark:bg-green-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Nombre</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Teléfono</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acción</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($clientes as $cliente)
            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700"
                x-show="$el.textContent.toLowerCase().includes(search.toLowerCase())">
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ str_pad($cliente->id, 3, '0', STR_PAD_LEFT) }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $cliente->nombre }} {{ $cliente->apellido }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $cliente->email }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $cliente->telefono }}
              </td>
              <td class="px-6 py-4 text-right">
                <a href="{{ route('depositos.show', $cliente->id) }}"
                   class="inline-flex items-center px-3 py-2 bg-green-500 hover:bg-green-600
                          text-white text-sm font-medium rounded-md">
                  Ver Depósitos
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                No hay clientes activos.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="px-4 py-3 text-right bg-gray-50 dark:bg-gray-700 sm:px-6">
        {{ $clientes->appends(['search'=>$search])->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
