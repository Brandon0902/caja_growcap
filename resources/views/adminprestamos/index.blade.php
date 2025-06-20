{{-- resources/views/prestamos/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Tipos de Préstamo') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{ search: '', filterStatus: '' }"
  >
    {{-- + Nuevo Tipo y filtros --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
      {{-- Botón “Nuevo Tipo” --}}
      <a href="{{ route('prestamos.create') }}"
         class="inline-flex items-center px-4 py-2
                bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-700 dark:hover:bg-yellow-800
                text-white font-semibold rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
        + Nuevo Tipo
      </a>

      <div class="flex space-x-2">
        {{-- Filtro por estado --}}
        <select x-model="filterStatus"
                class="px-3 py-2 sm:w-48 border rounded-md shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-purple-500
                       bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                       dark:border-gray-600">
          <option value="">{{ __('Todos los estados') }}</option>
          <option value="1">{{ __('Activo') }}</option>
          <option value="0">{{ __('Inactivo') }}</option>
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

    {{-- Mensaje de éxito --}}
    @if(session('success'))
      <div class="mb-4 rounded-lg bg-yellow-100 p-4 text-yellow-800
                  dark:bg-yellow-900 dark:text-yellow-200">
        {{ session('success') }}
      </div>
    @endif

    {{-- Panel de tabla --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
      <div class="overflow-x-auto">
        <table class="table-auto min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-purple-700 dark:bg-purple-900">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Periodo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Semanas</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Interés</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Mín-Máx</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Antigüedad</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase">Activo</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($prestamos as $p)
              <tr x-show="
                $el.textContent.toLowerCase().includes(search.toLowerCase())
                && (!filterStatus || filterStatus == '{{ $p->status == '1' ? '1' : '0' }}')
              " class="last:border-0">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ str_pad($p->id_prestamo,3,'0',STR_PAD_LEFT) }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $p->periodo }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $p->semanas }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $p->interes }}%</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $p->monto_minimo }} - {{ $p->monto_maximo }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $p->antiguedad }} meses</td>
                <td class="px-6 py-4 text-center">
                  @if($p->status == '1')
                    <span class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold text-yellow-800
                                 dark:bg-yellow-900 dark:text-yellow-200">
                      Sí
                    </span>
                  @else
                    <span class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold text-gray-800
                                 dark:bg-gray-700 dark:text-gray-300">
                      No
                    </span>
                  @endif
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  {{-- Ver --}}
                  <a href="{{ route('prestamos.show', $p) }}"
                     class="inline-flex items-center text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-100">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7
                               C20.268 16.057 16.477 19 12 19s-8.268-2.943-9.542-7z" />
                    </svg>
                  </a>

                  {{-- Editar --}}
                  <a href="{{ route('prestamos.edit', $p) }}"
                     class="inline-flex items-center text-yellow-600 hover:text-yellow-800 dark:text-yellow-300 dark:hover:text-yellow-100">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0
                               002 2h11a2 2 0 002-2v-5" />
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4
                               9.5-9.5z" />
                    </svg>
                  </a>

                  {{-- Desactivar / Activar --}}
                  <form id="toggle-form-{{ $p->id_prestamo }}"
                        action="{{ route('prestamos.destroy', $p) }}"
                        method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="button"
                            data-id="{{ $p->id_prestamo }}"
                            data-active="{{ $p->status == '1' ? '1' : '0' }}"
                            class="btn-toggle inline-flex items-center p-2 rounded-full transition
                                   {{ $p->status == '1'
                                       ? 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600'
                                       : 'bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600' }}">
                      @if($p->status == '1')
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-5 w-5 text-white"
                             viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm-2.293-9.707a1 1 0
                                   011.414 0L10 8.586l1.293-1.293a1 1 0
                                   111.414 1.414L11.414 10l1.293 1.293a1 1 0
                                   01-1.414 1.414L10 11.414l-1.293 1.293a1 1 0
                                   01-1.414-1.414L8.586 10l-1.293-1.293a1 1 0
                                   010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                      @else
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-5 w-5 text-white"
                             viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0
                                   00-1.414-1.414L9 10.586 7.707 9.293a1 1 0
                                   00-1.414 1.414l2 2a1 1 0
                                   001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                      @endif
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8"
                    class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  {{ __('No hay tipos de préstamo registrados.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Paginación --}}
      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $prestamos->links() }}
      </div>
    </div>
  </div>

  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.querySelectorAll('.btn-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const id     = btn.dataset.id,
              active = btn.dataset.active === '1',
              verb   = active ? 'desactivar' : 'activar';

        Swal.fire({
          title: `¿Deseas ${verb} este tipo?`,
          text:  `Esta acción marcará el tipo de préstamo como ${ active ? 'inactivo' : 'activo' }.`,
          icon:  'question',
          showCancelButton: true,
          confirmButtonText: `Sí, ${verb}`,
          cancelButtonText: 'Cancelar',
          confirmButtonColor: active ? '#d33' : '#3085d6',
          cancelButtonColor: '#aaa'
        }).then(result => {
          if (result.isConfirmed) {
            document.getElementById(`toggle-form-${id}`).submit();
          }
        });
      });
    });
  </script>
</x-app-layout>