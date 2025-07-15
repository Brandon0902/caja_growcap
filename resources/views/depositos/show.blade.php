{{-- resources/views/depositos/show.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      Depósitos de {{ $cliente->nombre }} {{ $cliente->apellido }}
    </h2>
  </x-slot>

  @php
    $statusLabels = [
      0 => 'Pendiente',
      1 => 'Aprobado',
      2 => 'Rechazado',
    ];
  @endphp

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div x-data="{
          openModal: false,
          modalData: { action: '', status: 0, nota: '' }
        }"
         class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-green-600 dark:bg-green-800">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Cantidad</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Nota</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acción</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($depositos as $d)
              <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $d->id }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">${{ number_format($d->cantidad,2) }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $d->fecha_deposito }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $d->nota }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $statusLabels[$d->status] }}
                </td>
                <td class="px-6 py-4 text-right">
                  <button
                    @click="modalData = {
                      action: '{{ route('depositos.update', $d->id) }}',
                      status: {{ $d->status }},
                      nota:       `{{ addslashes($d->nota) }}`
                    }; openModal = true;"
                    class="inline-flex px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md"
                  >
                    Editar
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  No hay depósitos registrados.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Modal de edición -->
      <div
        x-show="openModal"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      >
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Editar Depósito
            </h3>
            <button @click="openModal = false"
                    class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
              &times;
            </button>
          </div>
          <form :action="modalData.action" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Status
              </label>
              <select
                name="status"
                x-model="modalData.status"
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                       focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                       text-gray-900 dark:text-gray-100"
                required
              >
                <option value="0">Pendiente</option>
                <option value="1">Aprobado</option>
                <option value="2">Rechazado</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Nota
              </label>
              <textarea
                name="nota"
                x-model="modalData.nota"
                rows="3"
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                       focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                       text-gray-900 dark:text-gray-100"
              ></textarea>
            </div>

            <div class="flex justify-end space-x-2">
              <button type="button"
                      @click="openModal = false"
                      class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md">
                Cancelar
              </button>
              <button type="submit"
                      class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">
                Guardar
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</x-app-layout>
