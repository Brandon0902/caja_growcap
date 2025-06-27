<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Clientes') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" x-data="{ search: '' }">
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center">
      <a href="{{ route('clientes.create') }}"
         class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md">
        + Nuevo Cliente
      </a>
      <input type="text" x-model="search" placeholder="Buscar…"
             class="px-3 py-2 border rounded-md"/>
    </div>

    @if(session('success'))
      <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
        {{ session('success') }}
      </div>
    @endif

    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 whitespace-nowrap">
        <thead class="bg-gray-800 text-white">
          <tr>
            <th class="px-4 py-2 text-left">ID</th>
            <th class="px-4 py-2 text-left">Código</th>
            <th class="px-4 py-2 text-left">Nombre</th>
            <th class="px-4 py-2 text-left">Email</th>
            <th class="px-4 py-2 text-left">Tipo</th>
            <th class="px-4 py-2 text-left">Status</th>
            <th class="px-4 py-2 text-left">Creado</th>
            <th class="px-4 py-2 text-left">Modif.</th>
            <th class="px-4 py-2 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
        @forelse($clientes as $c)
          <tr x-show="$el.textContent.toLowerCase().includes(search.toLowerCase())">
            <td class="px-4 py-2">{{ $c->id }}</td>
            <td class="px-4 py-2">{{ $c->codigo_cliente }}</td>
            <td class="px-4 py-2">{{ $c->nombre }} {{ $c->apellido }}</td>
            <td class="px-4 py-2">{{ $c->email }}</td>
            <td class="px-4 py-2">{{ $c->tipo }}</td>
            <td class="px-4 py-2">
              @if($c->status)
                <span class="px-2 py-1 bg-green-100 text-green-800 rounded">Activo</span>
              @else
                <span class="px-2 py-1 bg-red-100 text-red-800 rounded">Inactivo</span>
              @endif
            </td>
            <td class="px-4 py-2">{{ optional($c->fecha)->format('d/m/Y') }}</td>
            <td class="px-4 py-2">{{ optional($c->fecha_edit)->format('d/m/Y') }}</td>
            <td class="px-4 py-2 text-right space-x-1">
              <a href="{{ route('clientes.show', $c) }}" class="text-blue-600 hover:underline">Ver</a>
              <a href="{{ route('clientes.edit', $c) }}" class="text-yellow-600 hover:underline">Editar</a>
              <form action="{{ route('clientes.destroy', $c) }}" method="POST" class="inline">
                @csrf @method('DELETE')
                <button onclick="return confirm('¿Desactivar cliente?')"
                        class="text-red-600 hover:underline">Inactivar</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="px-4 py-6 text-center text-gray-500">
              No hay clientes registrados.
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
</x-app-layout>
