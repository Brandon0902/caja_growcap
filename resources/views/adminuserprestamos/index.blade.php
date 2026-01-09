<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center gap-3">
      <a href="{{ route('user_prestamos.create') }}"
         class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md shadow">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        {{ __('Nuevo préstamo') }}
      </a>

      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Préstamos (todos)') }}
      </h2>
    </div>
  </x-slot>

  <style>
    [x-cloak]{ display:none!important; }
    .modal-backdrop{ background: rgba(0,0,0,.55); }
  </style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="userPrestamosIndexPage()"
       x-init="init()">

    @if(session('success'))
      <div class="mb-4 rounded bg-green-100 p-3 text-green-800 dark:bg-green-900 dark:text-green-200">
        {{ session('success') }}
      </div>
    @endif

    {{-- ===== Barra: Buscar + Filtros + Limpiar ===== --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="relative w-full sm:max-w-md">
        <input type="text" x-model="search"
               @input.debounce.400ms="liveSearch()" @keydown.enter.prevent
               placeholder="{{ __('Buscar cliente / # / monto…') }}"
               class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500
                      bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" />
        <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>

      <div class="flex items-center gap-2">
        <span x-show="hasActiveFilters()" x-cloak
              class="hidden sm:inline-flex text-xs font-medium px-2 py-1 rounded-full
                     bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200">
          Filtros activos
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
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto" id="prestamos-results">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
        <thead class="bg-green-700 dark:bg-green-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">#</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Cliente</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Tipo</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Monto</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">% Interés</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Int. generado</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Inicio</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Caja</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($prestamos as $p)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ str_pad($p->id, 3, '0', STR_PAD_LEFT) }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ optional($p->cliente)->nombre }} {{ optional($p->cliente)->apellido }}
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ optional($p->cliente)->email }}</div>
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $p->tipo_prestamo }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">${{ number_format($p->cantidad,2) }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ number_format($p->interes,2) }}%</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">${{ number_format($p->interes_generado,2) }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ \Carbon\Carbon::parse($p->fecha_inicio)->format('Y-m-d') }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ [1=>'Autorizado',2=>'Pendiente',3=>'En revisión',4=>'Rechazado',5=>'Pagado',6=>'Terminado'][$p->status] ?? '—' }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ optional($p->caja)->nombre ?? '—' }}</td>
              <td class="px-6 py-4 text-right">
                <a href="{{ route('user_prestamos.show', $p) }}"
                   class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md">
                  {{ __('Ver') }}
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                {{ __('No hay préstamos registrados.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="px-4 py-3 text-right bg-gray-50 dark:bg-gray-700 sm:px-6">
        {{ $prestamos->links() }}
      </div>
    </div>

    {{-- ===== MODAL FILTROS ===== --}}
    <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
      <div class="absolute inset-0 modal-backdrop" @click="closeFilters()"></div>

      <div class="relative w-full sm:max-w-3xl bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl shadow-xl
                  border border-gray-200 dark:border-gray-700 p-4 sm:p-6"
           @keydown.escape.window="closeFilters()">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Filtros</h3>
          <button type="button" @click="closeFilters()"
                  class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 dark:text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18"/><path d="M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Status</label>
            <select x-model="status"
                    class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
              @foreach($statusOptions as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div class="sm:col-span-1 lg:col-span-2">
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Orden</label>
            <select x-model="orden"
                    class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
              <option value="fecha_desc">{{ __('Más recientes') }}</option>
              <option value="fecha_asc">{{ __('Más antiguos') }}</option>
              <option value="monto_desc">{{ __('Monto ↓') }}</option>
              <option value="monto_asc">{{ __('Monto ↑') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Desde</label>
            <input type="date" x-model="desde"
                   class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Hasta</label>
            <input type="date" x-model="hasta"
                   class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" />
          </div>
        </div>

        <div class="mt-6 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2">
          <button type="button" @click="clearFilters()"
                  class="inline-flex justify-center px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700
                         text-gray-800 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600">
            Limpiar filtros
          </button>

          <div class="flex gap-2">
            <button type="button" @click="closeFilters()"
                    class="inline-flex justify-center px-4 py-2 rounded-md border border-gray-200 dark:border-gray-700
                           text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
              Cancelar
            </button>
            <button type="button" @click="applyFilters()"
                    class="inline-flex justify-center px-4 py-2 rounded-md bg-green-600 hover:bg-green-700 text-white shadow">
              Aplicar
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>

  {{-- Alpine helpers --}}
  <script>
    function userPrestamosIndexPage() {
      return {
        search: @json($search ?? ''),
        status: @json($status ?? ''),
        desde:  @json($desde  ?? ''),
        hasta:  @json($hasta  ?? ''),
        orden:  @json($orden  ?? 'fecha_desc'),

        loading: false,
        container: null,
        filtersOpen: false,

        init() {
          this.container = document.getElementById('prestamos-results');

          // Interceptar SOLO paginación (?page=) dentro del contenedor
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
                 (this.desde ?? '')  !== '' ||
                 (this.hasta ?? '')  !== '' ||
                 (this.orden ?? 'fecha_desc') !== 'fecha_desc';
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
          this.orden  = 'fecha_desc';

          const base = @json(route('user_prestamos.index'));
          window.location = base;
        },

        buildUrl() {
          const base = @json(route('user_prestamos.index'));
          const params = new URLSearchParams({
            search: this.search ?? '',
            status: this.status ?? '',
            desde:  this.desde  ?? '',
            hasta:  this.hasta  ?? '',
            orden:  this.orden  ?? 'fecha_desc',
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

            let htmlToInject = text;
            try {
              const doc  = new DOMParser().parseFromString(text, 'text/html');
              const frag = doc.querySelector('#prestamos-results');
              if (frag) htmlToInject = frag.innerHTML;
            } catch (_) {}

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
      }
    }
  </script>
</x-app-layout>
