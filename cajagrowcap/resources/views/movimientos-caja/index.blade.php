{{-- resources/views/movimientos_caja/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Movimientos de Caja') }}
    </h2>
  </x-slot>

  <style>[x-cloak]{ display:none!important; }</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="movCajaIndexPage()">

    {{-- + Nuevo Movimiento y filtros --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
      {{-- Botón “Nuevo Movimiento” --}}
      <a href="{{ route('movimientos-caja.create') }}"
         class="inline-flex items-center px-4 py-2
                bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-700 dark:hover:bg-yellow-800
                text-white font-semibold rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
        + {{ __('Nuevo Movimiento') }}
      </a>

      <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
        {{-- Filtro por caja --}}
        <select x-model="filterCaja" @change="liveSearch()"
                class="px-3 py-2 sm:w-48 border rounded-md shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-purple-500
                       bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                       dark:border-gray-600">
          <option value="">{{ __('Todas las cajas') }}</option>
          @foreach($movimientos->pluck('caja')->unique('id_caja') as $c)
            @if($c)
              <option value="{{ $c->id_caja }}">{{ $c->nombre }}</option>
            @endif
          @endforeach
        </select>

        {{-- Filtro por tipo --}}
        <select x-model="filterTipo" @change="liveSearch()"
                class="px-3 py-2 sm:w-40 border rounded-md shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-purple-500
                       bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                       dark:border-gray-600">
          <option value="">{{ __('Todos los tipos') }}</option>
          <option value="ingreso">{{ __('Ingreso') }}</option>
          <option value="gasto">{{ __('Gasto') }}</option>
        </select>

        {{-- Buscador --}}
        <div class="relative">
          <input type="text"
                 x-model="search"
                 @input.debounce.400ms="liveSearch()"
                 @keydown.enter.prevent
                 placeholder="{{ __('Buscar…') }}"
                 class="px-3 py-2 border rounded-md shadow-sm
                        focus:outline-none focus:ring-2 focus:ring-purple-500
                        bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                        dark:border-gray-600"/>
          {{-- spinner --}}
          <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
            <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
          </svg>
        </div>
      </div>
    </div>

    @if(session('success'))
      <div class="mb-4 rounded-lg bg-yellow-100 p-4 text-yellow-800
                  dark:bg-yellow-900 dark:text-yellow-200">
        {{ session('success') }}
      </div>
    @endif

    {{-- Contenedor reemplazable por AJAX --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg" id="movimientos-results">
      <div class="overflow-x-auto">
        <table class="table-auto min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-purple-700 dark:bg-purple-900">
            <tr>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Fecha</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Caja</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Tipo</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Categoría</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Subcategoría</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Proveedor</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">ID Origen</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Monto</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Saldo Ant.</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Saldo Post.</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase">Usuario</th>
              <th class="px-4 py-2 text-xs font-medium text-white uppercase text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($movimientos as $m)
              <tr>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ $m->fecha->format('Y-m-d H:i') }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ optional($m->caja)->nombre ?? '-' }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ ucfirst($m->tipo_mov) }}</td>

                {{-- Categoría --}}
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  @if (strtolower($m->tipo_mov) === 'ingreso')
                    {{ optional($m->categoriaIngreso)->nombre ?? '-' }}
                  @else
                    {{ optional($m->categoriaGasto)->nombre ?? '-' }}
                  @endif
                </td>

                {{-- Subcategoría --}}
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  @if (strtolower($m->tipo_mov) === 'ingreso')
                    {{ optional($m->subcategoriaIngreso)->nombre ?? '-' }}
                  @else
                    {{ optional($m->subcategoriaGasto)->nombre ?? '-' }}
                  @endif
                </td>

                {{-- Proveedor / Cliente (contraparte) --}}
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ optional($m->proveedor)->nombre ?? '-' }}
                </td>

                {{-- ID Origen --}}
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  @if($m->origen_id)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full
                                 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600
                                 font-mono text-xs text-gray-700 dark:text-gray-200">
                      #{{ $m->origen_id }}
                    </span>
                  @else
                    <span class="text-gray-400">—</span>
                  @endif
                </td>

                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ number_format($m->monto,2) }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ number_format($m->monto_anterior,2) }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ number_format($m->monto_posterior,2) }}
                </td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ optional($m->usuario)->name ?? '-' }}
                </td>
                <td class="px-4 py-2 text-right space-x-2">
                  <a href="{{ route('movimientos-caja.edit', $m) }}"
                     class="inline-flex items-center text-yellow-500 hover:text-yellow-700">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4
                               9.5-9.5z" />
                    </svg>
                  </a>
                  <form action="{{ route('movimientos-caja.destroy', $m) }}"
                        method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="button" class="inline-flex items-center text-red-500 hover:text-red-700 btn-delete"
                            data-id="{{ $m->id_mov }}">
                      <svg xmlns="http://www.w3.org/2000/svg"
                           class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M6 2a1 1 0 00-1 1v1H3a1 1 0
                                 100 2h14a1 1 0 100-2h-2V3a1 1 0
                                 00-1-1H6zm3 7a1 1 0 012 0v6a1 1 0
                                 11-2 0V9zm-4 0a1 1 0 012 0v6a1 1 0
                                 11-2 0V9z" clip-rule="evenodd"/>
                      </svg>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="12"
                    class="px-4 py-2 text-center text-gray-500 dark:text-gray-400">
                  {{ __('No hay movimientos registrados.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Paginación --}}
      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $movimientos->links() }}
      </div>
    </div>
  </div>

  {{-- SweetAlert2 para confirmar borrado --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    function movCajaIndexPage() {
      return {
        search: @json(request('search','')),
        filterCaja: @json(request('caja_id','')),
        filterTipo: @json(request('tipo','')),
        loading: false,
        container: null,

        init() {
          this.container = document.getElementById('movimientos-results');

          // Paginación AJAX (solo enlaces con ?page= dentro del contenedor)
          this.container?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            if (!/([?&])page=/.test(a.href)) return; // no interferir con Editar
            e.preventDefault();
            this.fetchTo(a.href);
          });

          // Delegación: confirmar borrado (funciona tras cada refresco)
          this.container?.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-delete');
            if (!btn) return;
            e.preventDefault();
            const form = btn.closest('form');
            Swal.fire({
              title: '¿Eliminar movimiento?',
              text: 'Esta acción no se puede deshacer.',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Sí, eliminar',
              cancelButtonText: 'Cancelar',
              confirmButtonColor: '#d33',
            }).then(result => {
              if (result.isConfirmed) {
                form.submit();
              }
            });
          });
        },

        // Construye URL con filtros para live search / paginación
        buildUrl() {
          const base = @json(route('movimientos-caja.index'));
          const params = new URLSearchParams({
            search:  this.search     ?? '',
            caja_id: this.filterCaja ?? '',
            tipo:    this.filterTipo ?? '',
            ajax: '1', // si el backend devuelve fragmento; si no, igual parseamos
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

            // Si viene la página completa, extraemos #movimientos-results
            let htmlToInject = text;
            try {
              const doc  = new DOMParser().parseFromString(text, 'text/html');
              const frag = doc.querySelector('#movimientos-results');
              if (frag) htmlToInject = frag.innerHTML;
            } catch (_) {}

            if (this.container) {
              this.container.innerHTML = htmlToInject;
              if (window.Alpine && Alpine.initTree) Alpine.initTree(this.container);
            }

            // Actualiza la URL visible (quitando ajax=1)
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
