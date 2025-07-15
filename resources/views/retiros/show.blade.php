{{-- resources/views/retiros/show.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-100 leading-tight">
      Retiros de {{ $cliente->nombre }} {{ $cliente->apellido }}
    </h2>
  </x-slot>

  @php
    $statusLabels = [
      0 => 'Solicitado',
      1 => 'Aprobado',
      2 => 'Pagado',
      3 => 'Rechazado',
    ];
  @endphp

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div x-data="{
          tab: 'inv',
          openModal: false,
          modalData: { action: '', tipo: '', cantidad: '', fecha: '', status: 0 }
        }"
         class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">

      <!-- Pestañas -->
      <div class="flex border-b border-gray-200 dark:border-gray-700">
        <button
          @click="tab = 'inv'"
          :class="tab === 'inv'
            ? 'border-green-600 text-green-600'
            : 'text-gray-500 dark:text-gray-400'"
          class="py-2 px-4 border-b-2 focus:outline-none">
          Retiros de Inversión
        </button>
        <button
          @click="tab = 'ahorro'"
          :class="tab === 'ahorro'
            ? 'border-green-600 text-green-600'
            : 'text-gray-500 dark:text-gray-400'"
          class="py-2 px-4 border-b-2 focus:outline-none">
          Retiros de Ahorro
        </button>
      </div>

      <!-- Tabla Retiros de Inversión -->
      <template x-if="tab === 'inv'">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
            <thead class="bg-green-600 dark:bg-green-800">
              <tr>
                @foreach(['Solicitud','Monto','Fecha Solicitud','Días','Status','Acciones'] as $col)
                  <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">
                    {{ $col }}
                  </th>
                @endforeach
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              @forelse($retirosInv as $r)
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $r->id }}</td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">${{ number_format($r->cantidad,2) }}</td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $r->fecha_solicitud }}</td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ now()->diffInDays($r->fecha_solicitud) }}</td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $statusLabels[$r->status] }}</td>
                  <td class="px-6 py-4 text-right">
                    <button
                      @click="modalData = {
                        action: '{{ route('retiros.inversion.update', $r->id) }}',
                        tipo: '{{ $r->tipo }}',
                        cantidad: {{ $r->cantidad }},
                        fecha: '{{ $r->fecha_solicitud }}',
                        status: {{ $r->status }}
                      }; openModal = true;"
                      class="inline-flex items-center px-3 py-2 bg-green-500 hover:bg-green-600
                             text-white text-sm font-medium rounded-md"
                    >
                      Editar
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    No hay retiros de inversión.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </template>

      <!-- Tabla Retiros de Ahorro -->
      <template x-if="tab === 'ahorro'">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
            <thead class="bg-green-600 dark:bg-green-800">
              <tr>
                @foreach(['Solicitud','Monto','Fecha Solicitud','Días','Status','Acciones'] as $col)
                  <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">
                    {{ $col }}
                  </th>
                @endforeach
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              @forelse($retirosAh as $r)
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $r->id }}</td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">${{ number_format($r->cantidad,2) }}</td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $r->fecha_solicitud }}</td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ now()->diffInDays($r->fecha_solicitud) }}</td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $statusLabels[$r->status] }}</td>
                  <td class="px-6 py-4 text-right">
                    <button
                      @click="modalData = {
                        action: '{{ route('retiros.ahorro.update', $r->id) }}',
                        tipo: '{{ $r->tipo }}',
                        cantidad: {{ $r->cantidad }},
                        fecha: '{{ $r->fecha_solicitud }}',
                        status: {{ $r->status }}
                      }; openModal = true;"
                      class="inline-flex items-center px-3 py-2 bg-green-500 hover:bg-green-600
                             text-white text-sm font-medium rounded-md"
                    >
                      Editar
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    No hay retiros de ahorro.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </template>

      <!-- Modal -->
      <div
        x-show="openModal"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      >
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Aprobar Retiro
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
                Tipo de Retiro
              </label>
              <select
                name="tipo"
                x-model="modalData.tipo"
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                       focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                       text-gray-900 dark:text-gray-100"
                required
              >
                <option value="Transferencia">Transferencia</option>
                <option value="Efectivo">Efectivo</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Cantidad
              </label>
              <input
                type="number"
                name="cantidad"
                x-model="modalData.cantidad"
                step="0.01"
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                       focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                       text-gray-900 dark:text-gray-100"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Fecha Solicitud
              </label>
              <input
                type="text"
                name="fecha_solicitud"
                x-model="modalData.fecha"
                readonly
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                       bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-200"
              />
            </div>

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
                <option value="">--</option>
                <option value="0">Solicitado</option>
                <option value="1">Aprobado</option>
                <option value="2">Pagado</option>
                <option value="3">Rechazado</option>
              </select>
            </div>

            <div class="pt-4 flex justify-end space-x-2">
              <button
                type="button"
                @click="openModal = false"
                class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md"
              >
                Cancelar
              </button>
              <button
                type="submit"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md"
              >
                Guardar
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</x-app-layout>
