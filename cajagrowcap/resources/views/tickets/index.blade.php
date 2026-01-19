{{-- resources/views/tickets/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-start gap-3">
      <a href="{{ route('tickets.create') }}"
         class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700
                text-white text-sm font-medium rounded-md shadow">
        + {{ __('Nuevo Ticket') }}
      </a>
      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Tickets') }}
      </h2>
    </div>
  </x-slot>

  <style>[x-cloak]{display:none!important}</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="ticketsIndexPage()"
       x-init="init()">

    @if(session('success'))
      <div class="mb-4 rounded-lg bg-green-100 dark:bg-green-900 p-4 text-green-800 dark:text-green-200">
        {{ session('success') }}
      </div>
    @endif

    {{-- Filtros / búsqueda --}}
    <div class="mb-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="relative sm:col-span-2">
        <input type="text"
               x-model="search"
               @input.debounce.400ms="liveSearch()"
               @keydown.enter.prevent
               placeholder="{{ __('Buscar por cliente / asunto / ID…') }}"
               class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500
                      bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>
        <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>

      <div class="flex gap-2">
        <select x-model="estado" @change="liveSearch()"
                class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
          <option value="">{{ __('Todos los estados') }}</option>
          <option value="abierto">{{ __('Abierto') }}</option>
          <option value="progreso">{{ __('En progreso') }}</option>
          <option value="resuelto">{{ __('Resuelto') }}</option>
          <option value="cerrado">{{ __('Cerrado') }}</option>
        </select>

        <select x-model="prioridad" @change="liveSearch()"
                class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
          <option value="">{{ __('Todas las prioridades') }}</option>
          <option value="baja">{{ __('Baja') }}</option>
          <option value="media">{{ __('Media') }}</option>
          <option value="alta">{{ __('Alta') }}</option>
          <option value="urgente">{{ __('Urgente') }}</option>
        </select>
      </div>
    </div>

    {{-- ✅ Tabla (partial reutilizable para AJAX) --}}
    @include('tickets.partials.results')

  </div>

  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    function ticketsIndexPage() {
      return {
        search: @json($search ?? request('search','')),
        estado: @json($estado ?? request('estado','')),
        prioridad: @json($prioridad ?? request('prioridad','')),
        loading: false,
        container: null,

        init() {
          this.container = document.getElementById('tickets-results');

          // Paginación AJAX: solo intercepta links con ?page=
          this.container?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            if (!/([?&])page=/.test(a.href)) return;
            e.preventDefault();
            this.fetchTo(a.href);
          });

          // Delegación: eliminar con SweetAlert
          this.container?.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-delete');
            if (!btn) return;
            e.preventDefault();
            const form = btn.closest('form');
            Swal.fire({
              title: '¿Eliminar ticket?',
              text: 'Esta acción no se puede deshacer.',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Sí, eliminar',
              cancelButtonText: 'Cancelar',
              confirmButtonColor: '#d33',
              cancelButtonColor: '#aaa',
            }).then(r => { if (r.isConfirmed) form.submit(); });
          });
        },

        buildUrl() {
          const base = @json(route('tickets.index'));
          const params = new URLSearchParams({
            search: this.search ?? '',
            estado: this.estado ?? '',
            prioridad: this.prioridad ?? '',
            ajax: '1',
          });
          return `${base}?${params.toString()}`;
        },

        async liveSearch() { await this.fetchTo(this.buildUrl()); },

        async fetchTo(url) {
          this.loading = true;
          try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const text = await res.text();

            let htmlToInject = text;
            try {
              const doc = new DOMParser().parseFromString(text, 'text/html');
              const frag = doc.querySelector('#tickets-results');
              if (frag) htmlToInject = frag.innerHTML;
            } catch(_) {}

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
