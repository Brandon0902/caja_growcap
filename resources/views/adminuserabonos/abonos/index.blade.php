<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Abonos del Préstamo #:id', ['id' => $prestamo->id]) }}
    </h2>
  </x-slot>

  <div 
    x-data="{
      isOpen: false,
      loading: false,
      content: '',
      openModal(url) {
        this.isOpen = true;
        this.loading = true;
        fetch(url)
          .then(res => res.text())
          .then(html => this.content = html)
          .finally(() => this.loading = false);
      },
      closeModal() {
        this.isOpen = false;
        this.content = '';
      }
    }"
    class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
  >

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 table-auto">
        <thead class="bg-green-600 dark:bg-green-800">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Cantidad</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase"># Pago</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Vence</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Mora</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Saldo</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @foreach($abonos as $a)
            <tr>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $a->id }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">${{ number_format($a->cantidad, 2) }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $a->fecha->format('Y-m-d') }}</td>
              <td class="px-6 py-4">
                <form action="{{ route('adminuserabonos.abonos.updateStatus', $a->id) }}" method="POST">
                  @csrf
                  <select name="status" onchange="this.form.submit()"
                          class="border-gray-300 rounded dark:bg-gray-700 dark:text-gray-200">
                    <option value="0" @selected($a->status==0)>Pendiente</option>
                    <option value="1" @selected($a->status==1)>Pagado</option>
                    <option value="2" @selected($a->status==2)>Vencido</option>
                  </select>
                </form>
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $a->num_pago }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $a->fecha_vencimiento->format('Y-m-d') }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">${{ number_format($a->mora_generada, 2) }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">${{ number_format($a->saldo_restante, 2) }}</td>
              <td class="px-6 py-4 text-right">
                <button
                  @click="openModal('{{ route('adminuserabonos.abonos.edit', $a->id) }}')"
                  class="inline-flex items-center px-3 py-2 bg-indigo-500 hover:bg-indigo-600 text-white text-sm font-medium rounded-md transition"
                >
                  <svg xmlns="http://www.w3.org/2000/svg"
                       class="h-4 w-4 mr-1"
                       fill="none" viewBox="0 0 24 24"
                       stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                  {{ __('Editar') }}
                </button>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- Modal de edición --}}
    <div
      x-show="isOpen"
      x-transition.opacity
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
      x-cloak
    >
      <div
        @click.away="closeModal()"
        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
      >
        {{-- Loading indicator --}}
        <template x-if="loading">
          <div class="p-6 text-center">{{ __('Cargando…') }}</div>
        </template>

        {{-- Contenido del modal --}}
        <template x-if="!loading">
          <div x-html="content"></div>
        </template>
      </div>
    </div>

  </div>
</x-app-layout>
