{{-- resources/views/adminahorros/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Ahorros') }}
    </h2>
  </x-slot>

  <style>[x-cloak]{display:none!important}</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{
         search: '',
         typing: false,
         clear(){ this.search=''; }
       }"
  >
    {{-- + Nuevo Ahorro y búsqueda --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
      <a href="{{ route('ahorros.create') }}"
         class="inline-flex items-center px-4 py-2
                bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-700 dark:hover:bg-yellow-800
                text-white font-semibold rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
        + {{ __('Nuevo Ahorro') }}
      </a>

      <div class="flex items-center gap-2 w-full sm:w-auto">
        <div class="relative flex-1 sm:w-96">
          <input type="text"
                 x-model.debounce.400ms="search"
                 @input="typing = true; setTimeout(()=>typing=false, 350)"
                 placeholder="{{ __('Buscar por nombre / categoría / meses / monto / tasa…') }}"
                 class="w-full px-3 py-2 border rounded-md shadow-sm
                        focus:outline-none focus:ring-2 focus:ring-purple-500
                        bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                        dark:border-gray-600"/>
          {{-- Spinner --}}
          <svg x-cloak x-show="typing"
               class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70"
               viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
            <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
          </svg>
        </div>

        <button @click="clear()"
                class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800
                       dark:bg-gray-700 dark:text-gray-200 rounded-md">
          {{ __('Limpiar') }}
        </button>
      </div>
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
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Nombre</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Meses Mín.</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Monto Mínimo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Categoría</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Tasa Vigente</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>

          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($ahorros as $i => $ahorro)
              <tr
                x-show="(($el.textContent || '').toLowerCase().includes((search || '').toLowerCase()))"
                class="hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ str_pad($ahorro->id, 3, '0', STR_PAD_LEFT) }}
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200 font-medium">
                  {{ $ahorro->nombre ?? '—' }}
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $ahorro->meses_minimos }}
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  ${{ number_format((float)$ahorro->monto_minimo, 2) }}
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $ahorro->tipo_ahorro }}
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ number_format((float)$ahorro->tasa_vigente, 2) }}%
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
                        method="POST" class="inline"
                        onsubmit="return confirm('{{ __('¿Eliminar este tipo de ahorro?') }}')">
                    @csrf
                    @method('DELETE')
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
                <td colspan="7"
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
