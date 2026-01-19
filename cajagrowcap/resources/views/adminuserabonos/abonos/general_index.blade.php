<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Abonos (Todos)') }}
    </h2>
  </x-slot>

  <style>
    [x-cloak]{ display:none !important; }
    .modal-backdrop{ background: rgba(0,0,0,.55); }
  </style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="abonosGeneralPage()"
       x-init="init()">

    {{-- ===== Barra: Buscar + Filtros + Limpiar ===== --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="relative w-full sm:max-w-md">
        <input type="text" x-model="search"
               @input.debounce.400ms="liveSearch()"
               @keydown.enter.prevent
               placeholder="{{ __('Buscar por cliente/email/ID/importe…') }}"
               class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>

        {{-- spinner --}}
        <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>

      <div class="flex items-center gap-2">
        <span x-show="hasActiveFilters()" x-cloak
              class="hidden sm:inline-flex text-xs font-medium px-2 py-1 rounded-full
                     bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200">
          {{ __('Filtros activos') }}
        </span>

        <button type="button"
                @click="openFilters()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800
                       border border-gray-200 dark:border-gray-700 rounded-md shadow-sm
                       text-sm font-medium text-gray-700 dark:text-gray-200
                       hover:bg-gray-50 dark:hover:bg-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 3H2l8 9v7l4 2v-9l8-9z"/>
          </svg>
          {{ __('Filtros') }}
        </button>

        <button type="button"
                @click="clearFilters()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700
                       text-gray-800 dark:text-gray-100 rounded-md shadow-sm
                       hover:bg-gray-200 dark:hover:bg-gray-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>
          </svg>
          {{ __('Limpiar') }}
        </button>
      </div>
    </div>

    {{-- ===== Resultados ===== --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto" id="abonos-results">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 table-auto whitespace-nowrap">
        <thead class="bg-green-700 dark:bg-green-900">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">#</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Cliente') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Préstamo') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('# Pago') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Cantidad') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Fecha') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Vence') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Mora') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Saldo') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Status') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase">{{ __('Acciones') }}</th>
          </tr>
        </thead>

        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($abonos as $a)
            @php $cliente = optional(optional($a->userPrestamo)->cliente); @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $a->id }}</td>

              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                {{ $cliente?->nombre }} {{ $cliente?->apellido }}
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $cliente?->email }}</div>
              </td>

              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">#{{ $a->user_prestamo_id }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $a->num_pago }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">${{ number_format((float)$a->cantidad, 2) }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ optional($a->fecha)->format('Y-m-d') }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ optional($a->fecha_vencimiento)->format('Y-m-d') }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">${{ number_format((float)$a->mora_generada, 2) }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">${{ number_format((float)$a->saldo_restante, 2) }}</td>

              <td class="px-4 py-3">
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

              <td class="px-4 py-3 text-right">
                <button @click="abrirModalEditar({{ $a->id }})"
                        class="inline-flex items-center px-3 py-2 bg-indigo-500 hover:bg-indigo-600 text-white text-sm rounded-md">
                  {{ __('Editar') }}
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                {{ __('No se encontraron abonos.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-right">
        {{ $abonos->appends(request()->query())->links() }}
      </div>
    </div>

    {{-- ===== MODAL FILTROS ===== --}}
    <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-40 flex items-end sm:items-center justify-center">
      <div class="absolute inset-0 modal-backdrop" @click="closeFilters()"></div>

      <div class="relative w-full sm:max-w-4xl bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl shadow-xl
                  border border-gray-200 dark:border-gray-700 p-4 sm:p-6"
           @keydown.escape.window="closeFilters()">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Filtros') }}</h3>
          <button type="button" @click="closeFilters()"
                  class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 dark:text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18"/><path d="M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ __('Status') }}</label>
            <select x-model="status"
                    class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
              @foreach($statusOptions as $v => $label)
                <option value="{{ $v }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ __('Orden') }}</label>
            <select x-model="orden"
                    class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
              <option value="recientes">{{ __('Más recientes') }}</option>
              <option value="antiguos">{{ __('Más antiguos') }}</option>
              <option value="monto_desc">{{ __('Monto ↓') }}</option>
              <option value="monto_asc">{{ __('Monto ↑') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ __('Desde') }}</label>
            <input type="date" x-model="desde"
                   class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">{{ __('Hasta') }}</label>
            <input type="date" x-model="hasta"
                   class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>
          </div>
        </div>

        <div class="mt-6 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2">
          <button type="button" @click="clearFilters()"
                  class="inline-flex justify-center px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700
                         text-gray-800 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600">
            {{ __('Limpiar filtros') }}
          </button>

          <div class="flex gap-2">
            <button type="button" @click="closeFilters()"
                    class="inline-flex justify-center px-4 py-2 rounded-md border border-gray-200 dark:border-gray-700
                           text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
              {{ __('Cancelar') }}
            </button>
            <button type="button" @click="applyFilters()"
                    class="inline-flex justify-center px-4 py-2 rounded-md bg-green-600 hover:bg-green-700 text-white shadow">
              {{ __('Aplicar') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- ===== MODAL EDICIÓN ===== --}}
    <div x-cloak x-show="showEdit" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @keydown.escape.window="cerrarModal()">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl relative">
        <button @click="cerrarModal()"
                class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white">
          ✕
        </button>
        <div class="p-4" x-html="editHtml"></div>
      </div>
    </div>

  </div>

  <script>
    function abonosGeneralPage() {
      return {
        // filtros (server)
        search: @json($search ?? ''),
        status: @json((string)($status ?? '')),
        desde:  @json($desde  ?? ''),
        hasta:  @json($hasta  ?? ''),
        orden:  @json($orden  ?? 'recientes'),

        // ui
        loading: false,
        container: null,

        // modal filtros
        filtersOpen: false,

        // modal editar
        showEdit: false,
        editHtml: '',

        init() {
          this.container = document.getElementById('abonos-results');

          // ✅ SOLO paginación (no romper forms/acciones)
          this.container?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            if (!/([?&])page=/.test(a.href)) return;
            e.preventDefault();
            this.fetchTo(a.href);
          });
        },

        openFilters(){ this.filtersOpen = true; },
        closeFilters(){ this.filtersOpen = false; },

        hasActiveFilters() {
          return (this.status ?? '') !== '' ||
                 (this.desde  ?? '') !== '' ||
                 (this.hasta  ?? '') !== '' ||
                 (this.orden  ?? 'recientes') !== 'recientes';
        },

        applyFilters() {
          this.closeFilters();
          this.liveSearch();
        },

        clearFilters() {
          this.search = '';
          this.status = '';
          this.desde  = '';
          this.hasta  = '';
          this.orden  = 'recientes';

          const base = @json(route('adminuserabonos.abonos.general'));
          window.location = base;
        },

        buildUrl() {
          const base = @json(route('adminuserabonos.abonos.general'));
          const params = new URLSearchParams({
            search: this.search ?? '',
            status: this.status ?? '',
            desde:  this.desde  ?? '',
            hasta:  this.hasta  ?? '',
            orden:  this.orden  ?? 'recientes',
            ajax:   '1',
          });
          return `${base}?${params.toString()}`;
        },

        async liveSearch() { await this.fetchTo(this.buildUrl()); },

        async fetchTo(url) {
          this.loading = true;
          try {
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const text = await res.text();

            // ✅ SOLO inyectar fragmento. Si no existe, navegar (evita "vista dentro de vista").
            let htmlToInject = null;
            try {
              const doc  = new DOMParser().parseFromString(text, 'text/html');
              const frag = doc.querySelector('#abonos-results');
              if (frag) htmlToInject = frag.innerHTML;
            } catch (_) {}

            if (!htmlToInject) {
              const u = new URL(url, window.location.origin);
              u.searchParams.delete('ajax');
              window.location = u.toString();
              return;
            }

            if (this.container) {
              this.container.innerHTML = htmlToInject;
              if (window.Alpine && Alpine.initTree) Alpine.initTree(this.container);
            }

            const u = new URL(url, window.location.origin);
            u.searchParams.delete('ajax');
            history.replaceState({}, '', u);

          } catch (e) {
            console.error('Live search error:', e);
          } finally {
            this.loading = false;
          }
        },

        async abrirModalEditar(id) {
          try {
            const url = @json(route('adminuserabonos.abonos.edit', '__ID__')).replace('__ID__', id);
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            if (!res.ok) throw new Error('HTTP ' + res.status);
            this.editHtml = await res.text();
            this.showEdit = true;
          } catch (e) {
            console.error(e);
            alert('No se pudo cargar el formulario de edición.');
          }
        },

        cerrarModal() {
          this.showEdit = false;
          this.editHtml = '';
        },
      }
    }
  </script>
</x-app-layout>
