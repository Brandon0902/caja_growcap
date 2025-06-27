<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Ahorros') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{ search: '' }"
  >
    {{-- + Nuevo Ahorro y búsqueda --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
      <a href="{{ route('ahorros.create') }}"
         class="inline-flex items-center px-4 py-2
                bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-700 dark:hover:bg-yellow-800
                text-white font-semibold rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
        + Nuevo Ahorro
      </a>

      <input type="text"
             x-model="search"
             placeholder="{{ __('Buscar…') }}"
             class="px-3 py-2 border rounded-md shadow-sm
                    focus:outline-none focus:ring-2 focus:ring-purple-500
                    bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                    dark:border-gray-600"/>
    </div>

    @if(session('success'))
      <div class="mb-4 rounded-lg bg-yellow-100 p-4 text-yellow-800
                  dark:bg-yellow-900 dark:text-yellow-200">
        {{ session('success') }}
      </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
      <div class="overflow-x-auto">
        <table class="table-auto min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-purple-700 dark:bg-purple-900">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Meses Mín.</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Monto Mínimo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Tipo de Ahorro</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Tasa Vigente</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($ahorros as $ahorro)
              <tr x-show="$el.textContent.toLowerCase().includes(search.toLowerCase())">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ str_pad($ahorro->id, 3, '0', STR_PAD_LEFT) }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $ahorro->meses_minimos }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $ahorro->monto_minimo }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $ahorro->tipo_ahorro }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $ahorro->tasa_vigente }}%
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  {{-- Ver --}}
                  <a href="{{ route('ahorros.show', $ahorro) }}"
                     class="inline-flex items-center text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-100">
                    <x-heroicon-o-eye class="h-5 w-5 mr-1"/>
                  </a>
                  {{-- Editar --}}
                  <a href="{{ route('ahorros.edit', $ahorro) }}"
                     class="inline-flex items-center text-yellow-600 hover:text-yellow-800 dark:text-yellow-300 dark:hover:text-yellow-100">
                    <x-heroicon-o-pencil class="h-5 w-5 mr-1"/>
                  </a>
                  {{-- Eliminar --}}
                  <form action="{{ route('ahorros.destroy', $ahorro) }}"
                        method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center p-2 rounded-full bg-red-600 hover:bg-red-700
                                   dark:bg-red-500 dark:hover:bg-red-600 transition">
                      <x-heroicon-o-trash class="h-5 w-5 text-white"/>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6"
                    class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  {{ __('No hay ahorros registrados.') }}
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
