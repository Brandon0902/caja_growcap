{{-- resources/views/sucursales/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Sucursales') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{ search: '', filterGerente: '' }"
  >
    {{-- + Nueva Sucursal y filtros --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
      {{-- Botón “Nueva Sucursal” --}}
      <a href="{{ route('sucursales.create') }}"
         class="inline-flex items-center px-4 py-2
                bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-700 dark:hover:bg-yellow-800
                text-white font-semibold rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
        + Nueva Sucursal
      </a>

      <div class="flex space-x-2">
        {{-- Filtro por gerente --}}
        <select x-model="filterGerente"
                class="px-3 py-2 sm:w-48 border rounded-md shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-purple-500
                       bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                       dark:border-gray-600">
          <option value="">{{ __('Todos los gerentes') }}</option>
          @foreach($gerentes as $g)
            <option value="{{ $g->id_usuario }}">{{ $g->name }}</option>
          @endforeach
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
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Nombre</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Dirección</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Teléfono</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Gerente</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase">Activa</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($sucursales as $s)
              <tr
                x-show="
                  // filtra por texto (todo el contenido de la fila)
                  $el.textContent.toLowerCase().includes(search.toLowerCase())
                  // y por gerente si se ha elegido uno
                  && (!filterGerente || filterGerente == '{{ $s->gerente_id }}')
                "
                class="last:border-0"
              >
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $s->nombre }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $s->direccion }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $s->telefono }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ optional($s->gerente)->name ?? '-' }}
                </td>
                <td class="px-6 py-4 text-center">
                  @if($s->acceso_activo)
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
                  <a href="{{ route('sucursales.show', $s) }}"
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
                    {{ __('Ver') }}
                  </a>

                  {{-- Editar --}}
                  <a href="{{ route('sucursales.edit', $s) }}"
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
                    {{ __('Editar') }}
                  </a>

                  {{-- Toggle Active --}}
                  <form id="toggle-form-{{ $s->id_sucursal }}"
                        action="{{ route('sucursales.toggle', $s) }}"
                        method="POST"
                        class="inline">
                    @csrf @method('PATCH')
                    <button type="button"
                            data-id="{{ $s->id_sucursal }}"
                            data-active="{{ $s->acceso_activo ? '1' : '0' }}"
                            class="btn-toggle inline-flex items-center p-2 rounded-full transition
                                   {{ $s->acceso_activo
                                       ? 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600'
                                       : 'bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600' }}">
                      @if($s->acceso_activo)
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
                <td colspan="6"
                    class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  {{ __('No hay sucursales registradas.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Paginación --}}
      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $sucursales->links() }}
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
          title: `¿Deseas ${verb} esta sucursal?`,
          text:  `Esta acción marcará la sucursal como ${ active ? 'inactiva' : 'activa' }.`,
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
