<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Inversiones') }}
    </h2>
  </x-slot>

  <style>
    [x-cloak]{ display:none!important; }
    .modal-backdrop{ background: rgba(0,0,0,.55); }
  </style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="window.inversionesIndexPage()"
       x-init="init()">

    @if(session('success'))
      <div class="mb-4 rounded-lg bg-green-100 dark:bg-green-900 p-4 text-green-800 dark:text-green-200">
        {{ session('success') }}
      </div>
    @endif

    {{-- ===== Barra: Buscar + Filtros + Limpiar ===== --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="relative w-full sm:max-w-md">
        <input type="text"
               x-model="search"
               @input.debounce.400ms="liveSearch()"
               @keydown.enter.prevent
               placeholder="{{ __('Buscar por cliente / # / monto…') }}"
               class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500
                      bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" />
        <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>

      <div class="flex items-center gap-2">
        <span x-show="hasActiveFilters()" x-cloak
              class="hidden sm:inline-flex text-xs font-medium px-2 py-1 rounded-full
                     bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200">
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
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg" id="inversiones-results">
      <div class="overflow-x-auto">
        <table class="table-auto min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-purple-700 dark:bg-purple-900">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">#</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Cliente</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Plan</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Interés</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Monto</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha inicio</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha fin</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Estatus</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>

          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($inversiones as $inv)
              @php
                $labels = [1=>'Pendiente', 2=>'Activa', 3=>'Inactiva'];
                $badgeClasses = [
                  1 => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                  2 => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                  3 => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                ];
              @endphp

              <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ str_pad($inv->id, 3, '0', STR_PAD_LEFT) }}
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ optional($inv->cliente)->nombre }} {{ optional($inv->cliente)->apellido }}
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ optional($inv->cliente)->email }}
                  </div>
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ optional($inv->plan)->periodo }}
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ number_format($inv->rendimiento ?? optional($inv->plan)->rendimiento ?? 0, 2) }}%
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  ${{ number_format($inv->inversion ?? 0, 2) }}
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $inv->fecha_inicio ? \Carbon\Carbon::parse($inv->fecha_inicio)->format('Y-m-d') : '—' }}
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $inv->fecha_fin ? \Carbon\Carbon::parse($inv->fecha_fin)->format('Y-m-d') : '—' }}
                </td>

                <td class="px-6 py-4">
                  <span class="px-2 py-1 rounded text-xs font-semibold {{ $badgeClasses[$inv->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                    {{ $labels[$inv->status] ?? '—' }}
                  </span>
                </td>

                <td class="px-6 py-4 text-right">
                  <a href="{{ route('user_inversiones.show', $inv) }}"
                     class="inline-flex items-center px-3 py-2 bg-yellow-500 hover:bg-yellow-600
                            text-white text-sm font-medium rounded-md shadow-sm focus:outline-none
                            focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
                    {{ __('Ver') }}
                  </a>
                  <a href="{{ route('user_inversiones.edit', $inv) }}"
                     class="inline-flex items-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700
                            text-white text-sm font-medium rounded-md shadow-sm focus:outline-none
                            focus:ring-2 focus:ring-offset-2 focus:ring-indigo-400 ml-2">
                    {{ __('Editar') }}
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  {{ __('No hay inversiones registradas.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $inversiones->links() }}
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
              @foreach($statusOptions as $key=>$label)
                <option value="{{ $key }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div class="sm:col-span-1 lg:col-span-2">
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Orden</label>
            <select x-model="orden"
                    class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
              <option value="fecha_inicio_desc">{{ __('Más recientes (inicio)') }}</option>
              <option value="fecha_inicio_asc">{{ __('Más antiguas (inicio)') }}</option>
              <option value="fecha_fin_desc">{{ __('Más recientes (fin)') }}</option>
              <option value="fecha_fin_asc">{{ __('Más antiguas (fin)') }}</option>
              <option value="monto_desc">{{ __('Monto ↓') }}</option>
              <option value="monto_asc">{{ __('Monto ↑') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Inicio desde</label>
            <input type="date" x-model="desde"
                   class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Inicio hasta</label>
            <input type="date" x-model="hasta"
                   class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Fin desde</label>
            <input type="date" x-model="fin_desde"
                   class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Fin hasta</label>
            <input type="date" x-model="fin_hasta"
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
                    class="inline-flex justify-center px-4 py-2 rounded-md bg-purple-600 hover:bg-purple-700 text-white shadow">
              Aplicar
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>

  @push('scripts')
  <script>
    window.inversionesIndexPage = function () {
      return {
        search: @json($search ?? ''),
        status: @json($status ?? ''),
        desde:  @json($desde  ?? ''),
        hasta:  @json($hasta  ?? ''),
        fin_desde: @json($fin_desde ?? ''),
        fin_hasta: @json($fin_hasta ?? ''),
        orden:  @json($orden  ?? 'fecha_inicio_desc'),

        loading: false,
        container: null,
        filtersOpen: false,

        init() {
          this.container = document.getElementById('inversiones-results');

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
                 (this.fin_desde ?? '') !== '' ||
                 (this.fin_hasta ?? '') !== '' ||
                 (this.orden ?? 'fecha_inicio_desc') !== 'fecha_inicio_desc';
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
          this.fin_desde = '';
          this.fin_hasta = '';
          this.orden  = 'fecha_inicio_desc';

          const base = @json(route('user_inversiones.index'));
          if (window.Turbo) Turbo.visit(base);
          else window.location = base;
        },

        buildUrl() {
          const base = @json(route('user_inversiones.index'));
          const params = new URLSearchParams({
            search: this.search ?? '',
            status: this.status ?? '',
            desde:  this.desde  ?? '',
            hasta:  this.hasta  ?? '',
            fin_desde: this.fin_desde ?? '',
            fin_hasta: this.fin_hasta ?? '',
            orden:  this.orden  ?? 'fecha_inicio_desc',
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
              const frag = doc.querySelector('#inversiones-results');
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
  @endpush
</x-app-layout>
