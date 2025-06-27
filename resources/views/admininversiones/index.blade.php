{{-- resources/views/admininversiones/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Inversiones') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{ search: '', filterStatus: '' }"
  >
    {{-- + Nueva Inversión y filtros --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
      <a href="{{ route('inversiones.create') }}"
         class="inline-flex items-center px-4 py-2
                bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-700 dark:hover:bg-yellow-800
                text-white font-semibold rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
        + Nueva Inversión
      </a>

      <div class="flex space-x-2">
        <select x-model="filterStatus"
                class="px-3 py-2 sm:w-48 border rounded-md shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-purple-500
                       bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                       dark:border-gray-600">
          <option value="">{{ __('Todos los estados') }}</option>
          <option value="1">{{ __('Activo') }}</option>
          <option value="0">{{ __('Inactivo') }}</option>
        </select>

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
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Periodo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Meses Mín.</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Mín–Máx</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Rendimiento</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase">Activo</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($inversiones as $inv)
              <tr x-show="
                $el.textContent.toLowerCase().includes(search.toLowerCase())
                && (!filterStatus || filterStatus == '{{ $inv->status }}')
              ">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ str_pad($inv->id, 3, '0', STR_PAD_LEFT) }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $inv->periodo }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $inv->meses_minimos }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $inv->monto_minimo }} – {{ $inv->monto_maximo }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $inv->rendimiento }}%</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ \Carbon\Carbon::parse($inv->fecha)->format('d/m/Y') }}
                </td>
                <td class="px-6 py-4 text-center">
                  @if($inv->status == '1')
                    <span class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold text-yellow-800
                                 dark:bg-yellow-900 dark:text-yellow-200">Sí</span>
                  @else
                    <span class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold text-gray-800
                                 dark:bg-gray-700 dark:text-gray-300">No</span>
                  @endif
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  {{-- Ver --}}
                  <a href="{{ route('inversiones.show', $inv) }}"
                     class="inline-flex items-center text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-100">
                    <x-heroicon-o-eye class="h-5 w-5 mr-1"/>
                  </a>
                  {{-- Editar --}}
                  <a href="{{ route('inversiones.edit', $inv) }}"
                     class="inline-flex items-center text-yellow-600 hover:text-yellow-800 dark:text-yellow-300 dark:hover:text-yellow-100">
                    <x-heroicon-o-pencil class="h-5 w-5 mr-1"/>
                  </a>
                  {{-- Activar/Desactivar --}}
                  <form id="toggle-form-{{ $inv->id }}"
                        action="{{ route('inversiones.destroy', $inv) }}"
                        method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="button"
                            data-id="{{ $inv->id }}"
                            data-active="{{ $inv->status }}"
                            class="btn-toggle inline-flex items-center p-2 rounded-full transition
                                   {{ $inv->status == '1'
                                       ? 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600'
                                       : 'bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600' }}">
                      @if($inv->status == '1')
                        <x-heroicon-o-x-mark class="h-5 w-5 text-white"/>
                      @else
                        <x-heroicon-o-check class="h-5 w-5 text-white"/>
                      @endif
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8"
                    class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
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
    document.querySelectorAll('.btn-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const id     = btn.dataset.id,
              active = btn.dataset.active === '1',
              verb   = active ? 'desactivar' : 'activar';

        Swal.fire({
          title: `¿Deseas ${verb} esta inversión?`,
          text:  `Esta acción marcará la inversión como ${active ? 'inactiva' : 'activa'}.`,
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
