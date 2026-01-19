<x-app-layout>
  <x-slot name="header">
    <div class="flex flex-wrap items-center justify-start gap-3">
      @role('admin')
        <a href="{{ route('depositos.create') }}"
           class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700
                  text-white text-sm font-medium rounded-md shadow">
          {{ __('+ Nuevo depósito (emergencia)') }}
        </a>
      @endrole

      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Depósitos') }}
      </h2>
    </div>
  </x-slot>

  <style>
    [x-cloak]{ display:none!important; }
    .modal-backdrop{ background: rgba(0,0,0,.55); }
  </style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="depositosIndexPage()"
       x-init="init()">

    @if(session('success'))
      <div class="mb-4 rounded-lg bg-green-100 dark:bg-green-900 p-4 text-green-800 dark:text-green-200">
        {{ session('success') }}
      </div>
    @endif

    {{-- ===== Header acciones: Buscar + botón Filtros + Limpiar ===== --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      {{-- Buscar (live) --}}
      <div class="relative w-full sm:max-w-md">
        <input type="text"
               x-model="search"
               @input.debounce.400ms="liveSearch()"
               @keydown.enter.prevent
               placeholder="{{ __('Buscar por cliente / # / nota / monto…') }}"
               class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500
                      bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" />

        {{-- spinner --}}
        <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>

      {{-- Botones derecha --}}
      <div class="flex items-center gap-2">
        <span x-show="hasActiveFilters()" x-cloak
              class="hidden sm:inline-flex text-xs font-medium px-2 py-1 rounded-full
                     bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200">
          Filtros activos
        </span>

        <button
          type="button"
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

        <button
          type="button"
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

    {{-- ===== Tabla ===== --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg" id="depositos-results">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-green-700 dark:bg-green-900">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">#</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Cliente</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Monto</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Caja</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>

          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($depositos as $dep)
              <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ str_pad($dep->id, 3, '0', STR_PAD_LEFT) }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ optional($dep->cliente)->nombre }} {{ optional($dep->cliente)->apellido }}
                  <div class="text-xs text-gray-500 dark:text-gray-400">{{ optional($dep->cliente)->email }}</div>
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">${{ number_format($dep->cantidad,2) }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ \Carbon\Carbon::parse($dep->fecha_deposito)->format('Y-m-d') }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ [0=>'Pendiente',1=>'Aprobado',2=>'Rechazado'][$dep->status] ?? '—' }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ optional($dep->caja)->nombre ?? '—' }}
                </td>
                <td class="px-6 py-4 text-right">
                  <a href="{{ route('depositos.show', $dep) }}"
                     class="inline-flex items-center px-3 py-2 bg-green-500 hover:bg-green-600
                            text-white text-sm font-medium rounded-md">
                    {{ __('Ver') }}
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                  {{ __('No hay depósitos registrados.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="px-4 py-3 text-right bg-gray-50 dark:bg-gray-700 sm:px-6">
        {{ $depositos->links() }}
      </div>
    </div>

    {{-- ===== MODAL FILTROS ===== --}}
    <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
      <div class="absolute inset-0 modal-backdrop" @click="closeFilters()"></div>

      <div
        class="relative w-full sm:max-w-2xl bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl shadow-xl
               border border-gray-200 dark:border-gray-700 p-4 sm:p-6"
        @keydown.escape.window="closeFilters()"
      >
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Filtros</h3>
          <button type="button" @click="closeFilters()"
                  class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 dark:text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18"/><path d="M6 6l12 12"/>
            </svg>
          </button>
        </div>

        {{-- Campos (editables en modal) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Status</label>
            <select x-model="status"
                    class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
              @foreach($statusOptions as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div>
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

        {{-- Acciones modal --}}
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
    function depositosIndexPage() {
      return {
        // Estado inicial desde el servidor
        search: @json($search ?? request('search','')),
        status: @json($status ?? request('status','')),
        desde:  @json($desde  ?? request('desde','')),
        hasta:  @json($hasta  ?? request('hasta','')),
        orden:  @json($orden  ?? request('orden','fecha_desc')),

        loading: false,
        container: null,

        filtersOpen: false,

        init() {
          this.container = document.getElementById('depositos-results');

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
          // aplica sin recargar completa
          this.closeFilters();
          this.liveSearch();
        },

        clearFilters() {
          // limpia todo y recarga “limpio”
          this.search = '';
          this.status = '';
          this.desde  = '';
          this.hasta  = '';
          this.orden  = 'fecha_desc';

          const base = @json(route('depositos.index'));
          window.location = base; // sin querystring
        },

        // Navegación normal (si la necesitas)
        go() {
          const base = @json(route('depositos.index'));
          const params = new URLSearchParams({
            search: this.search ?? '',
            status: this.status ?? '',
            desde:  this.desde  ?? '',
            hasta:  this.hasta  ?? '',
            orden:  this.orden  ?? 'fecha_desc',
          });
          window.location = `${base}?${params.toString()}`;
        },

        buildUrl() {
          const base = @json(route('depositos.index'));
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

        async liveSearch() {
          await this.fetchTo(this.buildUrl());
        },

        async fetchTo(url) {
          this.loading = true;
          try {
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const text = await res.text();

            // Si viene vista completa, extraer #depositos-results
            let htmlToInject = text;
            try {
              const doc  = new DOMParser().parseFromString(text, 'text/html');
              const frag = doc.querySelector('#depositos-results');
              if (frag) htmlToInject = frag.innerHTML;
            } catch (_) {}

            if (this.container) {
              this.container.innerHTML = htmlToInject;
              if (window.Alpine && Alpine.initTree) Alpine.initTree(this.container);
            }

            // Actualiza URL visible (quitando ajax)
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
