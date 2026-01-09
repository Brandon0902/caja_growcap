{{-- resources/views/sucursales/index.blade.php --}}
<x-app-layout>
     <x-slot name="header">
      <div class="flex items-center justify-start gap-3">
        <a href="{{ route('sucursales.create') }}"
           class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700
                  text-white text-sm font-medium rounded-md shadow-sm focus:outline-none
                  focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
          {{ __('Nueva Sucursal') }}
        </a>
    
        <h2 class="font-semibold text-xl text-white leading-tight">
          {{ __('Sucursales') }}
        </h2>
      </div>
    </x-slot>


  <style>[x-cloak]{display:none!important}</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="sucursalesIndexPage()">

    @if(session('success'))
      <div class="mb-4 rounded-lg bg-green-100 p-4 text-green-800 dark:bg-green-900 dark:text-green-200">
        {{ session('success') }}
      </div>
    @endif

    {{-- Buscador dinámico --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
      <div class="relative w-full sm:w-96">
        <input type="text"
               x-model="search"
               @input.debounce.400ms="liveSearch()"
               @keydown.enter.prevent
               placeholder="Buscar sucursal…"
               class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500
                      bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>
        {{-- spinner --}}
        <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>
    </div>

    {{-- Contenedor reemplazable por AJAX --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden" id="sucursales-results">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">Nombre</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">Dirección</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">Teléfono</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">Gerente</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">Acceso Activo</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($sucursales as $sucursal)
            <tr>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ $sucursal->nombre }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ $sucursal->direccion }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ $sucursal->telefono }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">{{ optional($sucursal->gerente)->name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                @if($sucursal->acceso_activo)
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Sí</span>
                @else
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">No</span>
                @endif
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <a href="{{ route('sucursales.edit', $sucursal) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('Editar') }}</a>

                <form action="{{ route('sucursales.destroy', $sucursal) }}" method="POST" class="inline">
                  @csrf @method('DELETE')
                  <button type="button"
                          class="text-red-600 hover:text-red-900 btn-delete"
                          data-id="{{ $sucursal->id_sucursal }}">
                    {{ __('Eliminar') }}
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ __('No hay sucursales registradas.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="mt-4 px-6">
        {{ $sucursales->appends(['search' => request('search')])->links() }}
      </div>
    </div>
  </div>

  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- Alpine helpers --}}
  <script>
    function sucursalesIndexPage() {
      return {
        search: @json($search ?? request('search','')),
        loading: false,
        container: null,

        init() {
          this.container = document.getElementById('sucursales-results');

          // Interceptar paginación (solo links con ?page= dentro del contenedor)
          this.container?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            if (!/([?&])page=/.test(a.href)) return; // ignora "editar"
            e.preventDefault();
            this.fetchTo(a.href);
          });

          // Delegación para borrar con SweetAlert (persiste tras refrescos)
          this.container?.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-delete');
            if (!btn) return;
            e.preventDefault();
            const form = btn.closest('form');
            Swal.fire({
              title: '¿Eliminar sucursal?',
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
          const base = @json(route('sucursales.index'));
          const params = new URLSearchParams({
            search: this.search ?? '',
            ajax: '1',
          });
          return `${base}?${params.toString()}`;
        },

        async liveSearch() {
          await this.fetchTo(this.buildUrl());
        },

        async fetchTo(url) {
          this.loading = true;
          try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const text = await res.text();

            // Si llega layout completo, extrae solo el fragmento
            let htmlToInject = text;
            try {
              const doc = new DOMParser().parseFromString(text, 'text/html');
              const frag = doc.querySelector('#sucursales-results');
              if (frag) htmlToInject = frag.innerHTML;
            } catch (_) {}

            if (this.container) {
              this.container.innerHTML = htmlToInject;
              if (window.Alpine && Alpine.initTree) Alpine.initTree(this.container);
            }

            // Actualiza la URL visible (quita ajax=1)
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
