<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Configuración de Mora') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{ search: '' }"
  >
    {{-- + Nueva Configuración y buscador --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
      <a href="{{ route('config_mora.create') }}"
         class="inline-flex items-center px-4 py-2
                bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-700 dark:hover:bg-yellow-800
                text-white font-semibold rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
        + Nueva Configuración
      </a>

      <input type="text"
             x-model="search"
             placeholder="{{ __('Buscar…') }}"
             class="px-3 py-2 border rounded-md shadow-sm
                    focus:outline-none focus:ring-2 focus:ring-purple-500
                    bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                    dark:border-gray-600"/>
    </div>

    {{-- Mensaje --}}
    @if(session('success'))
      <div class="mb-4 rounded-lg bg-yellow-100 p-4 text-yellow-800
                  dark:bg-yellow-900 dark:text-yellow-200">
        {{ session('success') }}
      </div>
    @endif

    {{-- Tabla --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
      <div class="overflow-x-auto">
        <table class="table-auto min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-purple-700 dark:bg-purple-900">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Cargo Fijo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">% Mora</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Período Gracia</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($configMoras as $mora)
              <tr x-show="$el.textContent.toLowerCase().includes(search.toLowerCase())" class="last:border-0">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $mora->id }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $mora->cargo_fijo }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $mora->porcentaje_mora }}%</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $mora->periodo_gracia }} días</td>
                <td class="px-6 py-4 text-right space-x-2">
                  {{-- Ver --}}
                  <a href="{{ route('config_mora.show', $mora) }}"
                     class="inline-flex items-center text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7
                               C20.268 16.057 16.477 19 12 19s-8.268-2.943-9.542-7z" />
                    </svg>
                  </a>
                  {{-- Editar --}}
                  <a href="{{ route('config_mora.edit', $mora) }}"
                     class="inline-flex items-center text-yellow-600 hover:text-yellow-800 dark:text-yellow-300 dark:hover:text-yellow-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0
                               002 2h11a2 2 0 002-2v-5" />
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4
                               9.5-9.5z" />
                    </svg>
                  </a>
                  {{-- Eliminar --}}
                  <form id="delete-form-{{ $mora->id }}"
                        action="{{ route('config_mora.destroy', $mora) }}"
                        method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="button"
                            data-id="{{ $mora->id }}"
                            class="btn-delete inline-flex items-center p-2 rounded-full
                                   bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 transition">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white"
                           viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M6 2a2 2 0 00-2 2v1H2v2h1v10a2 2 0 002 2h10a2 2 0
                                 002-2V7h1V5h-2V4a2 2 0 00-2-2H6zm2 3V4h4v1H8z"
                              clip-rule="evenodd" />
                      </svg>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5"
                    class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  {{ __('No hay configuraciones de mora registradas.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Paginación --}}
      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $configMoras->links() }}
      </div>
    </div>
  </div>

  {{-- SweetAlert2 para confirmación de borrado --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        Swal.fire({
          title: '¿Eliminar esta configuración?',
          text: 'Esta acción no se puede deshacer.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#d33',
          cancelButtonColor: '#aaa'
        }).then(result => {
          if (result.isConfirmed) {
            document.getElementById(`delete-form-${id}`).submit();
          }
        });
      });
    });
  </script>
</x-app-layout>
