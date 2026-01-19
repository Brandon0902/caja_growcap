{{-- resources/views/admininversiones/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Inversiones') }}
    </h2>
  </x-slot>

  <style>[x-cloak]{display:none!important}</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{
         search: '',
         filterStatus: '',
         typing: false,
         clearFilters(){ this.search=''; this.filterStatus=''; }
       }"
  >
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
      <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('inversiones.create') }}"
           class="inline-flex items-center px-4 py-2
                  bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-700 dark:hover:bg-yellow-800
                  text-white font-semibold rounded-md shadow-sm focus:outline-none
                  focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
          + {{ __('Nueva Inversion') }}
        </a>
      </div>

      <div class="flex items-center gap-2 w-full sm:w-auto">
        <select x-model="filterStatus"
                class="px-3 py-2 sm:w-48 border rounded-md shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-purple-500
                       bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                       dark:border-gray-600">
          <option value="">{{ __('Todos los estados') }}</option>
          <option value="1">{{ __('Activo') }}</option>
          <option value="2">{{ __('Inactivo') }}</option>
        </select>

        <div class="relative w-full sm:w-80">
          <input type="text"
                 x-model.debounce.400ms="search"
                 @input="typing = true; setTimeout(()=>typing=false, 350)"
                 placeholder="{{ __('Buscar por nombre/periodo/montos/rendimiento/fecha…') }}"
                 class="w-full px-3 py-2 border rounded-md shadow-sm
                        focus:outline-none focus:ring-2 focus:ring-purple-500
                        bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                        dark:border-gray-600"/>

          <svg x-cloak x-show="typing"
               class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70"
               viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
            <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
          </svg>
        </div>

        <button @click="clearFilters()"
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
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Periodo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Min–Max</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Rendimiento</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase">Estado</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>

          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($inversiones as $inv)
              <tr
                x-show="
                  ($el.textContent || '').toLowerCase().includes((search || '').toLowerCase())
                  && (!filterStatus || String(filterStatus) === String('{{ $inv->status }}'))
                "
                class="hover:bg-gray-100 dark:hover:bg-gray-700"
              >
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ str_pad($inv->id, 3, '0', STR_PAD_LEFT) }}
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $inv->nombre ?? ('Inversion '.$inv->periodo.' meses') }}
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $inv->periodo }}</td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $inv->monto_minimo }} – {{ $inv->monto_maximo }}
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $inv->rendimiento }}%</td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ \Carbon\Carbon::parse($inv->fecha)->format('d/m/Y') }}
                </td>

                {{-- ✅ Estado rápido por SELECT (sin flecha nativa, con flecha custom) --}}
                <td class="px-6 py-4 text-center">
                      <form id="status-form-{{ $inv->id }}"
                            action="{{ route('inversiones.updateStatus', $inv) }}"
                            method="POST"
                            class="inline">
                        @csrf
                        @method('PATCH')
                    
                        <span style="position:relative; display:inline-block;">
                          <select
                            class="status-select min-w-[150px] w-40 px-3 py-2"
                            style="
                              padding-right: 2.5rem;
                              border:1px solid #6b7280;
                              border-radius: .375rem;
                              background-image:none !important;
                              appearance:none;
                              -webkit-appearance:none;
                              -moz-appearance:none;
                            "
                            name="status"
                            data-id="{{ $inv->id }}"
                            data-current="{{ (string)$inv->status }}"
                          >
                            <option value="1" @selected((string)$inv->status === '1')>Activo</option>
                            <option value="2" @selected((string)$inv->status === '2')>Inactivo</option>
                          </select>
                    
                          <svg style="position:absolute; right:12px; top:50%; transform:translateY(-50%); pointer-events:none;"
                               width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z" clip-rule="evenodd"/>
                          </svg>
                        </span>
                      </form>
                    </td>


                <td class="px-6 py-4 text-right space-x-2">
                  <a href="{{ route('inversiones.show', $inv) }}"
                     class="inline-flex items-center text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-100">
                    <x-heroicon-o-eye class="h-5 w-5 mr-1"/>
                  </a>

                  <a href="{{ route('inversiones.edit', $inv) }}"
                     class="inline-flex items-center text-yellow-600 hover:text-yellow-800 dark:text-yellow-300 dark:hover:text-yellow-100">
                    <x-heroicon-o-pencil class="h-5 w-5 mr-1"/>
                  </a>

                  {{-- Borrado permanente (X) --}}
                  <form id="force-form-{{ $inv->id }}"
                        action="{{ route('inversiones.forceDestroy', $inv) }}"
                        method="POST" class="inline">
                    @csrf
                    @method('DELETE')

                    <button type="button"
                            class="btn-force inline-flex items-center p-2 rounded-full transition
                                   bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600"
                            data-id="{{ $inv->id }}">
                      <x-heroicon-o-x-mark class="h-5 w-5 text-white"/>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  {{ __('No hay inversiones registradas.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $inversiones->links() }}
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Cambio rápido de estado (select)
    document.querySelectorAll('.status-select').forEach(sel => {
      sel.addEventListener('change', async () => {
        const id = sel.dataset.id;
        const current = String(sel.dataset.current);
        const next = String(sel.value);

        if (current === next) return;

        const txt = next === '1' ? 'activar' : 'desactivar';

        const res = await Swal.fire({
          title: `¿Deseas ${txt} esta inversion?`,
          text:  `Se marcara como ${next === '1' ? 'activa' : 'inactiva'}.`,
          icon:  'question',
          showCancelButton: true,
          confirmButtonText: `Si, ${txt}`,
          cancelButtonText: 'Cancelar',
          confirmButtonColor: next === '1' ? '#3085d6' : '#d33',
          cancelButtonColor: '#aaa'
        });

        if (!res.isConfirmed) {
          sel.value = current; // revert
          return;
        }

        document.getElementById(`status-form-${id}`).submit();
      });
    });

    // Borrado permanente (X)
    document.querySelectorAll('.btn-force').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.id;

        const res = await Swal.fire({
          title: '¿Eliminar permanentemente?',
          text: 'Esta accion NO se puede deshacer.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Si, eliminar',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#d33',
          cancelButtonColor: '#aaa'
        });

        if (res.isConfirmed) {
          document.getElementById(`force-form-${id}`).submit();
        }
      });
    });
  </script>
</x-app-layout>
