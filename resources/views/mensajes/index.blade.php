<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      Mensajes
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-4">
      <a href="{{ route('mensajes.create') }}"
         class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm">
        Nuevo Mensaje
      </a>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-green-600 dark:bg-green-800">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Destinatario</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Asunto</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha Envío</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Estado</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($mensajes as $m)
              <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $m->id }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $m->id_cliente
                      ? $m->cliente->nombre . ' ' . $m->cliente->apellido
                      : 'Todos los clientes' }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $m->asunto }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ optional($m->fecha_envio)->format('Y-m-d H:i') ?: '—' }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  @if($m->status)
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                      Activo
                    </span>
                  @else
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                      Inactivo
                    </span>
                  @endif
                </td>
                <td class="px-6 py-4 text-right space-x-1">
                  <a href="{{ route('mensajes.show',$m) }}"
                     class="inline-flex px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded">
                    Ver
                  </a>
                  <a href="{{ route('mensajes.edit',$m) }}"
                     class="inline-flex px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium rounded">
                    Editar
                  </a>
                  <form action="{{ route('mensajes.destroy',$m) }}" method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button onclick="return confirm('¿Seguro?')"
                            class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded">
                      Borrar
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  No hay mensajes registrados.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $mensajes->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
