{{-- resources/views/retiros/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-start gap-3">
      @can('retiros.crear')
        <button
          x-data
          @click="$dispatch('open-create-retiro')"
          class="px-3 py-2 text-sm rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
          Realizar Retiro
        </button>
      @endcan

      <h2 class="font-semibold text-xl text-white leading-tight">Retiros</h2>
    </div>
  </x-slot>

  @php
    $statusLabels = [
      0 => 'Solicitado',
      1 => 'Aprobado',
      2 => 'Pagado',
      3 => 'Rechazado',
    ];
    $initTab = request('tab', $tab ?? 'inv'); // 'inv' | 'ahorro'
  @endphp

  <style>[x-cloak]{display:none!important}</style>

  <div
    class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
    x-data="retirosIndexPage({ openCreateOnLoad: {{ $errors->any() ? 'true' : 'false' }} })"
    x-on:open-create-retiro.window="openCreate = true"
    x-init="init()"
  >
    @if (session('ok'))
      <div class="mb-4 p-3 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">
        {{ session('ok') }}
      </div>
    @endif

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="relative w-full sm:w-96">
        <input
          type="text"
          x-model="search"
          @input.debounce.450ms="liveSearch()"
          @keydown.enter.prevent
          placeholder="Buscar por cliente, email, ID o tipo…"
          class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-600
                 bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"
        />
        <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>

      <div class="flex border-b border-gray-200 dark:border-gray-700">
        <button
          @click="switchTab('inv')"
          :class="tab === 'inv' ? 'border-green-600 text-green-600' : 'text-gray-500 dark:text-gray-400'"
          class="py-2 px-4 border-b-2 focus:outline-none">
          Retiros de Inversión
        </button>
        <button
          @click="switchTab('ahorro')"
          :class="tab === 'ahorro' ? 'border-green-600 text-green-600' : 'text-gray-500 dark:text-gray-400'"
          class="py-2 px-4 border-b-2 focus:outline-none">
          Retiros de Ahorro
        </button>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6" id="retiros-results">
      {{-- TABLA: INVERSIÓN --}}
      <template x-if="tab === 'inv'">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
            <thead class="bg-green-600 dark:bg-green-800">
              <tr>
                @foreach(['Solicitud','Cliente','Monto','Fecha Solicitud','Días','Status','Acciones'] as $col)
                  <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ $col }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              @forelse($retirosInv as $r)
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">#{{ $r->id }}</td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                    {{ $r->cliente_nombre }} {{ $r->cliente_apellido }}
                    <div class="text-xs text-gray-500">{{ $r->cliente_email }}</div>
                  </td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                    ${{ number_format((float)$r->cantidad, 2) }}
                  </td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                    {{ \Carbon\Carbon::parse($r->fecha_solicitud)->format('Y-m-d') }}
                  </td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                    {{ \Carbon\Carbon::parse($r->fecha_solicitud)->diffInDays(now()) }}
                  </td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                    {{ $statusLabels[(int)$r->status] ?? $r->status }}
                  </td>
                  <td class="px-6 py-4 text-right">
                    <button
                      @click="
                        modalData = {
                          action: '{{ route('retiros.inversion.update', $r->id) }}',
                          tipo: @js($r->tipo),
                          cantidad: {{ (float) $r->cantidad }},
                          fecha: @js($r->fecha_solicitud),
                          status: {{ (int) $r->status }},
                          id_caja: @js($r->id_caja ?? '')
                        };
                        openModal = true;
                      "
                      class="inline-flex items-center px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md">
                      {{ ((int)$r->status === 2) ? 'Ver' : 'Editar' }}
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    No hay retiros de inversión.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>

          <div class="mt-4">
            {{ $retirosInv->appends(['search' => $search ?? '', 'tab' => 'inv'])->links() }}
          </div>
        </div>
      </template>

      {{-- TABLA: AHORRO --}}
      <template x-if="tab === 'ahorro'">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
            <thead class="bg-green-600 dark:bg-green-800">
              <tr>
                @foreach(['Solicitud','Cliente','Monto','Fecha Solicitud','Días','Status','Acciones'] as $col)
                  <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ $col }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              @forelse($retirosAh as $r)
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">#{{ $r->id }}</td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                    {{ $r->cliente_nombre }} {{ $r->cliente_apellido }}
                    <div class="text-xs text-gray-500">{{ $r->cliente_email }}</div>
                  </td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                    ${{ number_format((float)$r->cantidad, 2) }}
                  </td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                    {{ \Carbon\Carbon::parse($r->fecha_solicitud)->format('Y-m-d') }}
                  </td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                    {{ \Carbon\Carbon::parse($r->fecha_solicitud)->diffInDays(now()) }}
                  </td>
                  <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                    {{ $statusLabels[(int)$r->status] ?? $r->status }}
                  </td>
                  <td class="px-6 py-4 text-right">
                    <button
                      @click="
                        modalData = {
                          action: '{{ route('retiros.ahorro.update', $r->id) }}',
                          tipo: @js($r->tipo),
                          cantidad: {{ (float) $r->cantidad }},
                          fecha: @js($r->fecha_solicitud),
                          status: {{ (int) $r->status }},
                          id_caja: @js($r->id_caja ?? '')
                        };
                        openModal = true;
                      "
                      class="inline-flex items-center px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md">
                      {{ ((int)$r->status === 2) ? 'Ver' : 'Editar' }}
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    No hay retiros de ahorro.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>

          <div class="mt-4">
            {{ $retirosAh->appends(['search' => $search ?? '', 'tab' => 'ahorro'])->links() }}
          </div>
        </div>
      </template>

      {{-- MODAL EDICIÓN --}}
      <div
        x-cloak
        x-show="openModal"
        x-transition.opacity
        class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-50"
      >
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              Actualizar Retiro
            </h3>
            <button @click="openModal = false" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">&times;</button>
          </div>

          <form :action="modalData.action" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')

            {{-- Aviso si está Pagado --}}
            <div
              x-show="Number(modalData.status) === 2"
              class="rounded-md border border-amber-200 bg-amber-50 p-3 text-amber-800"
            >
              Este retiro ya está <strong>Pagado</strong>. Por seguridad, no se permiten cambios.
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tipo de Retiro</label>
              <select name="tipo" x-model="modalData.tipo"
                      :disabled="Number(modalData.status) === 2"
                      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                             focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                             text-gray-900 dark:text-gray-100 disabled:opacity-60 disabled:cursor-not-allowed" required>
                <option value="Transferencia">Transferencia</option>
                <option value="Efectivo">Efectivo</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Cantidad</label>
              <input type="number" name="cantidad" x-model.number="modalData.cantidad" step="0.01"
                     :disabled="Number(modalData.status) === 2"
                     class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                            focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                            text-gray-900 dark:text-gray-100 disabled:opacity-60 disabled:cursor-not-allowed" required />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Si el retiro ya se descontó en el API, no debes cambiar este valor.
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Fecha Solicitud</label>
              <input type="text" name="fecha_solicitud" x-model="modalData.fecha" readonly
                     class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                            bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-200" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Status</label>
              <select name="status" x-model="modalData.status"
                      :disabled="Number(modalData.status) === 2"
                      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                             focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                             text-gray-900 dark:text-gray-100 disabled:opacity-60 disabled:cursor-not-allowed" required>
                <option value="0">Solicitado</option>
                <option value="1">Aprobado</option>
                <option value="2">Pagado</option>
                <option value="3">Rechazado</option>
              </select>
            </div>

            {{-- ✅ Caja --}}
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Caja</label>
              <select name="id_caja" x-model="modalData.id_caja"
                      :disabled="Number(modalData.status) === 2"
                      :required="Number(modalData.status) === 2"
                      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                             focus:ring-green-500 focus:border-green-500 bg-white dark:bg-gray-700
                             text-gray-900 dark:text-gray-100 disabled:opacity-60 disabled:cursor-not-allowed">
                <option value="">— Selecciona —</option>
                @foreach ($cajas as $cx)
                  <option value="{{ $cx->id }}">{{ $cx->nombre }}</option>
                @endforeach
              </select>

              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="Number(modalData.status) === 2">
                Obligatorio si lo marcas como “Pagado”.
              </p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="Number(modalData.status) !== 2">
                Puedes dejarlo vacío si aún no se paga.
              </p>
            </div>

            <div class="pt-4 flex justify-end gap-2">
              <button type="button" @click="openModal = false"
                      class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md">
                Cerrar
              </button>

              <button
                type="submit"
                :disabled="Number(modalData.status) === 2"
                :class="Number(modalData.status) === 2 ? 'bg-green-300 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                class="px-4 py-2 text-white rounded-md transition-colors">
                Guardar
              </button>
            </div>
          </form>
        </div>
      </div>

      {{-- MODAL CREACIÓN --}}
      @can('retiros.crear')
      <div
        x-cloak
        x-show="openCreate"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
      >
        <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-xl p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Realizar Retiro</h3>
            <button @click="openCreate = false" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">&times;</button>
          </div>

          <form method="POST" action="{{ route('retiros.store') }}" class="space-y-4">
            @csrf

            @if ($errors->any())
              <div class="rounded-md border border-red-200 bg-red-50 p-3 text-red-800">
                <div class="font-semibold mb-1">Revisa los siguientes errores:</div>
                <ul class="list-disc pl-5 space-y-0.5">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                Cliente <span class="text-rose-500">*</span>
              </label>
              <select name="cliente_id" x-model="createForm.clienteId"
                      class="w-full rounded-md border-gray-300 dark:bg-gray-800 dark:text-gray-100 focus:ring-indigo-500">
                <option value="">— Selecciona —</option>
                @foreach ($clientes as $c)
                  <option value="{{ $c->id }}" @selected(old('cliente_id') == $c->id)>{{ $c->nombre }}</option>
                @endforeach
              </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                  Origen <span class="text-rose-500">*</span>
                </label>
                <select name="tipo" x-model="createForm.tipo"
                        class="w-full rounded-md border-gray-300 dark:bg-gray-800 dark:text-gray-100 focus:ring-indigo-500">
                  <option value="ahorro"    @selected(old('tipo')==='ahorro')>Ahorro</option>
                  <option value="inversion" @selected(old('tipo')==='inversion')>Inversión</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                  Caja (opcional)
                </label>
                <select name="id_caja"
                        class="w-full rounded-md border-gray-300 dark:bg-gray-800 dark:text-gray-100 focus:ring-indigo-500">
                  <option value="">— Sin caja —</option>
                  @foreach ($cajas as $cx)
                    <option value="{{ $cx->id }}" @selected(old('id_caja') == $cx->id)>{{ $cx->nombre }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                Monto a retirar <span class="text-rose-500">*</span>
              </label>
              <div class="flex">
                <input type="number" name="monto" x-model.number="createForm.monto" step="0.01" min="0.01"
                       value="{{ old('monto') }}"
                       class="flex-1 rounded-l-md border-gray-300 dark:bg-gray-800 dark:text-gray-100 focus:ring-indigo-500" />
                <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-200 text-sm">
                  MXN
                </span>
              </div>
              <div class="mt-1 text-xs">
                <span class="text-gray-500">Previsualización:</span>
                <span class="font-medium text-gray-700 dark:text-gray-200" x-text="previewMonto()"></span>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                Nota (opcional)
              </label>
              <input type="text" name="nota" value="{{ old('nota') }}"
                     class="w-full rounded-md border-gray-300 dark:bg-gray-800 dark:text-gray-100 focus:ring-indigo-500" />
            </div>

            <div class="pt-2 flex justify-end gap-2">
              <button type="button" class="px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100"
                      @click="openCreate=false">
                Cancelar
              </button>
              <button type="submit"
                      :disabled="!puedeEnviar()"
                      :class="puedeEnviar() ? 'bg-indigo-600 hover:bg-indigo-700 cursor-pointer' : 'bg-indigo-300 cursor-not-allowed'"
                      class="px-4 py-2 rounded-md text-white transition-colors">
                Registrar retiro
              </button>
            </div>
          </form>
        </div>
      </div>
      @endcan
    </div>
  </div>

  <script>
    function retirosIndexPage({ openCreateOnLoad = false } = {}) {
      return {
        tab: @json($initTab),
        search: @json($search ?? request('search','')),
        loading: false,
        container: null,

        openModal: false,
        modalData: { action:'', tipo:'', cantidad:'', fecha:'', status:0, id_caja:'' },

        openCreate: openCreateOnLoad,
        createForm: {
          clienteId: @json(old('cliente_id', '')),
          tipo:      @json(old('tipo', 'ahorro')),
          monto:     Number(@json(old('monto', ''))) || null,
        },

        init() {
          this.container = document.getElementById('retiros-results');
        },

        // Nota: tu liveSearch() no está incluido en el snippet original.
        // Si ya lo tienes en otra parte, perfecto. Si no, elimina la llamada.
        liveSearch() {},

        switchTab(newTab) {
          if (this.tab === newTab) return;
          this.tab = newTab;
          const u = new URL(window.location.href);
          u.searchParams.set('tab', newTab);
          u.searchParams.set('search', this.search ?? '');
          history.replaceState({}, '', u);
        },

        previewMonto() {
          const m = this.createForm.monto;
          if (!m) return '—';
          try {
            return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(m);
          } catch { return Number(m).toFixed(2); }
        },
        puedeEnviar() {
          return String(this.createForm.clienteId).length > 0
              && ['ahorro','inversion'].includes(this.createForm.tipo)
              && this.createForm.monto !== null
              && this.createForm.monto > 0;
        },
      }
    }
  </script>
</x-app-layout>
