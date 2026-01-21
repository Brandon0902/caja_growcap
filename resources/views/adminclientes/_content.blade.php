@unless($panel ?? false)
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Clientes') }}
    </h2>
  </x-slot>
@endunless

<style>[x-cloak]{display:none!important}</style>

<div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
     x-data="{ search:'', typing:false, clear(){this.search=''} }">

  <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
    @can('clientes.crear')
    <a href="{{ route('clientes.create') }}"
       class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md shadow">
      + {{ __('Nuevo Cliente') }}
    </a>
    @endcan

    <div class="flex items-center gap-2 w-full sm:w-auto">
      <div class="relative flex-1 sm:w-96">
        <input type="text"
               x-model.debounce.400ms="search"
               @input="typing = true; setTimeout(()=>typing=false, 350)"
               placeholder="{{ __('Buscar por nombre / email / código / tipo…') }}"
               class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500
                      bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>
        <svg x-cloak x-show="typing" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>
      <button @click="clear()" class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded-md">
        {{ __('Limpiar') }}
      </button>
    </div>
  </div>

  @if(session('success'))
    <div class="mb-4 p-4 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100">
      {{ session('success') }}
    </div>
  @endif

  <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
      <thead class="bg-gray-800 text-white dark:bg-gray-900">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium uppercase">ID</th>
          <th class="px-4 py-2 text-left text-xs font-medium uppercase">Código</th>
          <th class="px-4 py-2 text-left text-xs font-medium uppercase">Nombre</th>
          <th class="px-4 py-2 text-left text-xs font-medium uppercase">Email</th>
          <th class="px-4 py-2 text-left text-xs font-medium uppercase">Tipo</th>
          <th class="px-4 py-2 text-left text-xs font-medium uppercase">Sucursal</th>
          <th class="px-4 py-2 text-left text-xs font-medium uppercase">Status</th>
          <th class="px-4 py-2 text-left text-xs font-medium uppercase">Creado</th>
          <th class="px-4 py-2 text-left text-xs font-medium uppercase">Modif.</th>
          <th class="px-4 py-2 text-right text-xs font-medium uppercase">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($clientes as $c)
          <tr x-show="($el.textContent || '').toLowerCase().includes((search || '').toLowerCase())"
              class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $c->id }}</td>
            <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $c->codigo_cliente }}</td>
            <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $c->nombre }} {{ $c->apellido }}</td>
            <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $c->email }}</td>
            <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $c->tipo }}</td>
            <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $c->sucursal->nombre ?? '—' }}</td>
            <td class="px-4 py-2">
              @if($c->status)
                <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">{{ __('Activo') }}</span>
              @else
                <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold">{{ __('Inactivo') }}</span>
              @endif
            </td>
            <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ optional($c->fecha)->format('d/m/Y') }}</td>
            <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ optional($c->fecha_edit)->format('d/m/Y') }}</td>
            <td class="px-4 py-2 text-right space-x-2">
              <a href="{{ route('clientes.show', $c) }}" class="text-blue-600 hover:underline">{{ __('Ver') }}</a>
              @can('clientes.editar')
              <a href="{{ route('clientes.edit', $c) }}" class="text-yellow-600 hover:underline">{{ __('Editar') }}</a>
              @endcan
              @can('clientes.eliminar')
              <form action="{{ route('clientes.destroy', $c) }}" method="POST" class="inline">
                @csrf @method('DELETE')
                <button onclick="return confirm('{{ __('¿Desactivar cliente?') }}')" class="text-red-600 hover:underline">
                  {{ __('Inactivar') }}
                </button>
              </form>
              @endcan
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="10" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
              {{ __('No hay clientes registrados.') }}
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">
      {{ $clientes->links() }}
    </div>
  </div>
</div>
