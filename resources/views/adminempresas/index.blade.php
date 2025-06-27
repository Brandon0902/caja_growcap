<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Empresas') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" x-data="{ search: '' }">
    {{-- + Nueva Empresa y búsqueda --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
      <a href="{{ route('empresas.create') }}"
         class="inline-flex items-center px-4 py-2
                bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-700 dark:hover:bg-yellow-800
                text-white font-semibold rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
        + Nueva Empresa
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
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Nombre</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">RFC</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Ciudad</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Estado</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">País</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Estatus</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Creación</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Modificación</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($empresas as $e)
              <tr x-show="$el.textContent.toLowerCase().includes(search.toLowerCase())">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ str_pad($e->id, 3, '0', STR_PAD_LEFT) }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $e->nombre }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $e->rfc }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $e->ciudad }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $e->estado }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $e->pais }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  @if($e->estatus)
                    <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold text-green-800
                                 dark:bg-green-900 dark:text-green-200">Activo</span>
                  @else
                    <span class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold text-red-800
                                 dark:bg-red-900 dark:text-red-200">Inactivo</span>
                  @endif
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ \Carbon\Carbon::parse($e->fecha_creacion)->format('d/m/Y H:i') }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ \Carbon\Carbon::parse($e->fecha_modificacion)->format('d/m/Y H:i') }}
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  <a href="{{ route('empresas.show', $e) }}"
                     class="inline-flex items-center text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-100">
                    <x-heroicon-o-eye class="h-5 w-5 mr-1"/>
                  </a>
                  <a href="{{ route('empresas.edit', $e) }}"
                     class="inline-flex items-center text-yellow-600 hover:text-yellow-800 dark:text-yellow-300 dark:hover:text-yellow-100">
                    <x-heroicon-o-pencil class="h-5 w-5 mr-1"/>
                  </a>
                  <form action="{{ route('empresas.destroy', $e) }}" method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center p-2 rounded-full bg-red-600 hover:bg-red-700
                                   dark:bg-red-500 dark:hover:bg-red-600 transition"
                            onclick="return confirm('¿Eliminar empresa?')">
                      <x-heroicon-o-trash class="h-5 w-5 text-white"/>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10"
                    class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  {{ __('No hay empresas registradas.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $empresas->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
