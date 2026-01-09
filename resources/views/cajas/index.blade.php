{{-- resources/views/cajas/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Cajas') }}
    </h2>
  </x-slot>

  <style>[x-cloak]{ display:none!important; }</style>

  @php
    // Mejor: pásalas desde el controlador si quieres (recomendado).
    // Pero así funciona directo sin depender de la página actual del paginador.
    $sucursalesFiltro = \App\Models\Sucursal::query()->orderBy('nombre')->get();
  @endphp

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="cajasIndexPage()"
       x-init="init()">

    {{-- + Nueva Caja y filtros --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
      {{-- Botón “Nueva Caja” --}}
      <a href="{{ route('cajas.create') }}"
         class="inline-flex items-center px-4 py-2
                bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-700 dark:hover:bg-yellow-800
                text-white font-semibold rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
        + Nueva Caja
      </a>

      <div class="flex space-x-2">
        {{-- Filtro por sucursal --}}
        <select x-model="sucursal"
                @change="liveSearch()"
                autocomplete="off"
                class="px-3 py-2 sm:w-48 border rounded-md shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-purple-500
                       bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                       dark:border-gray-600">
          <option value="">{{ __('Todas las sucursales') }}</option>
          @foreach($sucursalesFiltro as $s)
            <option value="{{ $s->id_sucursal }}">{{ $s->nombre }}</option>
          @endforeach
        </select>

        {{-- Buscador --}}
        <div class="relative">
          <input type="text"
                 x-model="search"
                 @input.debounce.400ms="liveSearch()"
                 @keydown.enter.prevent
                 placeholder="{{ __('Buscar…') }}"
                 autocomplete="off"
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

    {{-- Mensaje de éxito --}}
    @if(session('success'))
      <div class="mb-4 rounded-lg bg-yellow-100 p-4 text-yellow-800
                  dark:bg-yellow-900 dark:text-yellow-200">
        {{ session('success') }}
      </div>
    @endif

    {{-- Panel de tabla (contenedor que se reemplaza por AJAX) --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg" id="cajas-results">
      <div class="overflow-x-auto">
        <table class="table-auto min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-purple-700 dark:bg-purple-900">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Nombre</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Sucursal</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Responsable</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Apertura</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Estado</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Saldo</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase">Activa</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>

          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($cajas as $c)
              @php $cajaId = $c->id_caja ?? $c->getKey(); @endphp
              <tr class="last:border-0">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $c->nombre }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ optional($c->sucursal)->nombre ?? '-' }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ optional($c->responsable)->name ?? '-' }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $c->fecha_apertura }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ ucfirst($c->estado) }}</td>

                {{-- SALDO --}}
                <td class="px-6 py-4 text-right text-gray-800 dark:text-gray-100 font-semibold tabular-nums">
                  ${{ number_format($c->saldo_actual ?? 0, 2) }}
                </td>

                <td class="px-6 py-4 text-center">
                  @if($c->acceso_activo)
                    <span class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold text-yellow-800
                                 dark:bg-yellow-900 dark:text-yellow-200">
                      Sí
                    </span>
                  @else
                    <span class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold text-gray-800
                                 dark:bg-gray-700 dark:text-gray-300">
                      No
                    </span>
                  @endif
                </td>

                <td class="px-6 py-4 text-right space-x-2">
                  {{-- Ver --}}
                  <a href="{{ route('cajas.show', $c) }}"
                     class="inline-flex items-center text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7
                               C20.268 16.057 16.477 19 12 19s-8.268-2.943-9.542-7z" />
                    </svg>
                    {{ __('Ver') }}
                  </a>

                  {{-- Editar --}}
                  <a href="{{ route('cajas.edit', $c) }}"
                     class="inline-flex items-center text-yellow-600 hover:text-yellow-800 dark:text-yellow-300 dark:hover:text-yellow-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                    {{ __('Editar') }}
                  </a>

                  {{-- Toggle Active --}}
                  <form id="toggle-form-{{ $cajaId }}"
                        action="{{ route('cajas.toggle', $c) }}"
                        method="POST"
                        class="inline">
                    @csrf @method('PATCH')
                    <button type="button"
                            data-id="{{ $cajaId }}"
                            data-active="{{ $c->acceso_activo ? '1' : '0' }}"
                            class="btn-toggle inline-flex items-center p-2 rounded-full transition
                                   {{ $c->acceso_activo
                                       ? 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600'
                                       : 'bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600' }}">
                      @if($c->acceso_activo)
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm-2.293-9.707a1 1 0
                                   011.414 0L10 8.586l1.293-1.293a1 1 0
                                   111.414 1.414L11.414 10l1.293 1.293a1 1 0
                                   01-1.414 1.414L10 11.414l-1.293 1.293a1 1 0
                                   01-1.414-1.414L8.586 10l-1.293-1.293a1 1 0
                                   010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                      @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0
                                   00-1.414-1.414L9 10.586 7.707 9.293a1 1 0
                                   00-1.414 1.414l2 2a1 1 0
                                   001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                      @endif
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  {{ __('No hay cajas registradas.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Paginación --}}
      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $cajas->links() }}
      </div>
    </div>
  </div>

  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    function cajasIndexPage() {
      return {
        search: '',
        sucursal: '',
        loading: false,
        container: null,

        init() {
          // Siempre toma el estado inicial desde la URL (no desde "request" cacheado).
          const params = new URLSearchParams(window.location.search);
          this.search   = params.get('search') ?? '';
          this.sucursal = params.get('sucursal_id') ?? '';

          this.container = document.getElementById('cajas-results');

          // Delegación: paginación AJAX (solo enlaces con page=)
          this.container?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            if (!/([?&])page=/.test(a.href)) return; // no tocar Ver/Editar
            e.preventDefault();
            this.fetchTo(a.href);
          });

          // Delegación: toggle activo con SweetAlert
          this.container?.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-toggle');
            if (!btn) return;
            e.preventDefault();

            const id     = btn.dataset.id;
            const active = btn.dataset.active === '1';
            const verb   = active ? 'desactivar' : 'activar';

            Swal.fire({
              title: `¿Deseas ${verb} esta caja?`,
              text:  `Esta acción marcará la caja como ${ active ? 'inactiva' : 'activa' }.`,
              icon:  'question',
              showCancelButton: true,
              confirmButtonText: `Sí, ${verb}`,
              cancelButtonText: 'Cancelar',
              confirmButtonColor: active ? '#d33' : '#3085d6',
              cancelButtonColor: '#aaa'
            }).then(result => {
              if (result.isConfirmed) {
                document.getElementById(`toggle-form-${id}`)?.submit();
              }
            });
          });
        },

        buildUrl() {
          const base = @json(route('cajas.index'));
          const params = new URLSearchParams();

          // ✅ Solo agrega params si tienen valor (para que no se “quede” seleccionado al recargar)
          if ((this.search ?? '').trim() !== '') params.set('search', this.search.trim());
          if ((this.sucursal ?? '').trim() !== '') params.set('sucursal_id', String(this.sucursal).trim());

          // Si usas render parcial del lado del servidor, déjalo; si no, igual sirve.
          params.set('ajax', '1');

          const qs = params.toString();
          return qs ? `${base}?${qs}` : base;
        },

        async liveSearch() {
          await this.fetchTo(this.buildUrl());
        },

        async fetchTo(url) {
          this.loading = true;
          try {
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const text = await res.text();

            // Si el backend devuelve la página completa, extraemos #cajas-results
            let htmlToInject = text;
            try {
              const doc  = new DOMParser().parseFromString(text, 'text/html');
              const frag = doc.querySelector('#cajas-results');
              if (frag) htmlToInject = frag.innerHTML;
            } catch (_) {}

            if (this.container) {
              this.container.innerHTML = htmlToInject;
              if (window.Alpine && Alpine.initTree) Alpine.initTree(this.container);
            }

            // ✅ Actualiza URL visible quitando ajax y quitando params vacíos
            const u = new URL(url, window.location.origin);
            u.searchParams.delete('ajax');

            if (!u.searchParams.get('search')) u.searchParams.delete('search');
            if (!u.searchParams.get('sucursal_id')) u.searchParams.delete('sucursal_id');

            // Si quedan params vacíos tipo sucursal_id=, lo borra también:
            if (u.searchParams.has('sucursal_id') && u.searchParams.get('sucursal_id') === '') {
              u.searchParams.delete('sucursal_id');
            }
            if (u.searchParams.has('search') && u.searchParams.get('search') === '') {
              u.searchParams.delete('search');
            }

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
