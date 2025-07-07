{{-- resources/views/user_data/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Datos de Cliente') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    @if(session('success'))
      <div class="mb-4 rounded-lg bg-green-100 p-4 text-green-800 dark:bg-green-900 dark:text-green-200">
        {{ session('success') }}
      </div>
    @endif

    <div class="mb-4 flex justify-end">
      <a href="{{ route('user_data.create') }}"
         class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded shadow">
        + Nuevo Registro
      </a>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase">Cliente</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase">Estado</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase">Municipio</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-200 uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @foreach($datos as $item)
            <tr>
              <td class="px-6 py-4 whitespace-nowrap">{{ $item->id }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                {{ optional($item->cliente)->nombre }} {{ optional($item->cliente)->apellido }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                {{ optional($item->estado)->nombre ?? '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                {{ optional($item->municipio)->nombre ?? '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                <a href="{{ route('user_data.edit', $item) }}"
                   class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded">
                  Editar
                </a>
                <form action="{{ route('user_data.destroy', $item) }}"
                      method="POST" class="inline-block"
                      onsubmit="return confirm('¿Desactivar este registro?');">
                  @csrf @method('DELETE')
                  <button type="submit"
                          class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded">
                    Desactivar
                  </button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="mt-4">
      {{ $datos->links() }}
    </div>
  </div>
</x-app-layout>
