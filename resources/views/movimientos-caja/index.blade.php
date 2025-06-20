{{-- resources/views/movimientos_caja/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Movimientos de Caja') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{ search: '', filterCaja: '', filterTipo: '' }"
  >
    {{-- + Nuevo Movimiento y filtros --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
      {{-- Botón “Nuevo Movimiento” --}}
      <a href="{{ route('movimientos-caja.create') }}"
         class="inline-flex items-center px-4 py-2
                bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-700 dark:hover:bg-yellow-800
                text-white font-semibold rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
        + Nuevo Movimiento
      </a>

      <div class="flex space-x-2">
        {{-- Filtro por caja --}}
        <select x-model="filterCaja"
                class="px-3 py-2 sm:w-48 border rounded-md shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-purple-500
                       bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                       dark:border-gray-600">
          <option value="">{{ __('Todas las cajas') }}</option>
          @foreach($movimientos->pluck('caja')->unique('id_caja') as $c)
            <option value="{{ $c->id_caja }}">{{ $c->nombre }}</option>
          @endforeach
        </select>

        {{-- Filtro por tipo --}}
        <select x-model="filterTipo"
                class="px-3 py-2 sm:w-40 border rounded-md shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-purple-500
                       bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                       dark:border-gray-600">
          <option value="">{{ __('Todos los tipos') }}</option>
          <option value="ingreso">{{ __('Ingreso') }}</option>
          <option value="gasto">{{ __('Gasto') }}</option>
        </select>

        {{-- Buscador --}}
        <input type="text"
               x-model="search"
               placeholder="{{ __('Buscar…') }}"
               class="px-3 py-2 border rounded-md shadow-sm
                      focus:outline-none focus:ring-2 focus:ring-purple-500
                      bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                      dark:border-gray-600"/>
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
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Fecha</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Caja</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Tipo</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Categoría</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Subcategoría</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Proveedor</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Monto</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Saldo Ant.</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Saldo Post.</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Usuario</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($movimientos as $m)
              <tr x-show="
                $el.textContent.toLowerCase().includes(search.toLowerCase())
                && (!filterCaja || filterCaja == '{{ $m->id_caja }}')
                && (!filterTipo || filterTipo == '{{ $m->tipo_mov }}')
              ">
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ $m->fecha->format('Y-m-d H:i') }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ optional($m->caja)->nombre ?? '-' }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ ucfirst($m->tipo_mov) }}</td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  @if($m->tipo_mov=='ingreso')
                    {{ optional($m->categoriaIngreso)->nombre ?? '-' }}
                  @else
                    {{ optional($m->categoriaGasto)->nombre ?? '-' }}
                  @endif
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  @if($m->tipo_mov=='ingreso')
                    {{ optional($m->subcategoriaIngreso)->nombre ?? '-' }}
                  @else
                    {{ optional($m->subcategoriaGasto)->nombre ?? '-' }}
                  @endif
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ optional($m->proveedor)->nombre ?? '-' }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ number_format($m->monto,2) }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ number_format($m->monto_anterior,2) }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ number_format($m->monto_posterior,2) }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ optional($m->usuario)->name ?? '-' }}
                </td>
                <td class="px-4 py-2 text-right space-x-2">
                  <a href="{{ route('movimientos-caja.edit', $m) }}"
                     class="inline-flex items-center text-yellow-500 hover:text-yellow-700">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4
                               9.5-9.5z" />
                    </svg>
                  </a>
                  <form action="{{ route('movimientos-caja.destroy', $m) }}"
                        method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="button" class="inline-flex items-center text-red-500 hover:text-red-700 btn-delete"
                            data-id="{{ $m->id_mov }}">
                      <svg xmlns="http://www.w3.org/2000/svg"
                           class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M6 2a1 1 0 00-1 1v1H3a1 1 0
                                 100 2h14a1 1 0 100-2h-2V3a1 1 0
                                 00-1-1H6zm3 7a1 1 0 012 0v6a1 1 0
                                 11-2 0V9zm-4 0a1 1 0 012 0v6a1 1 0
                                 11-2 0V9z" clip-rule="evenodd"/>
                      </svg>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="11"
                    class="px-4 py-2 text-center text-gray-500 dark:text-gray-400">
                  {{ __('No hay movimientos registrados.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Paginación --}}
      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $movimientos->links() }}
      </div>
    </div>
  </div>

  {{-- SweetAlert2 para confirmar borrado --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        Swal.fire({
          title: '¿Eliminar movimiento?',
          text: 'Esta acción no se puede deshacer.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#d33',
        }).then(result => {
          if (result.isConfirmed) {
            btn.closest('form').submit();
          }
        });
      });
    });
  </script>
</x-app-layout>
