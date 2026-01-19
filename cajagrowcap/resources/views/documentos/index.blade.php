{{-- resources/views/documentos/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      Gestión de Documentos de Clientes
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-100 dark:bg-gray-700">
          <tr>
            <th class="px-4 py-2 text-left text-gray-700 dark:text-gray-200">
              Cliente
            </th>
            <th class="px-4 py-2 text-left text-gray-700 dark:text-gray-200">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @foreach($users as $ud)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                {{ optional($ud->cliente)->nombre }}
                {{ optional($ud->cliente)->apellido }}
              </td>
              <td class="px-4 py-2">
                <a href="{{ route('documentos.show', $ud) }}"
                   class="inline-block px-3 py-1 bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white rounded transition">
                  Ver documentos
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="mt-4">
      {{ $users->links() }}
    </div>
  </div>
</x-app-layout>
