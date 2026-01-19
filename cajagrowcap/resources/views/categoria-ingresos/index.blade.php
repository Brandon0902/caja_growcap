{{-- resources/views/categoria-ingresos/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Categorías de Ingreso') }}
    </h2>
  </x-slot>

  <style>[x-cloak]{ display:none!important; }</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="catIngresosIndexPage()">

    <div class="mb-4 flex justify-between items-center gap-3">
      <a href="{{ route('categoria-ingresos.create') }}"
         class="inline-flex items-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-md">
        + {{ __('Nueva Categoría') }}
      </a>

      <div class="relative w-full max-w-sm">
        <input type="text"
               x-model="search"
               @input.debounce.400ms="liveSearch()"
               @keydown.enter.prevent
               placeholder="{{ __('Buscar…') }}"
               class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500
                      bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>
        {{-- spinner --}}
        <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>
    </div>

    @if(session('success'))
      <div class="mb-4 p-4 bg-green-100 text-green-800 rounded dark:bg-green-900 dark:text-green-100">
        {{ session('success') }}
      </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden" id="cat-ingresos-results">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-purple-700 dark:bg-purple-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Nombre') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Creado por') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Fecha') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">{{ __('Acciones') }}</th>
          </tr>
        </thead>

        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($categorias as $cat)
            <tr>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $cat->nombre }}</td>

              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ optional($cat->usuario)->name ?? '—' }}
              </td>

              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ optional($cat->created_at)->format('Y-m-d H:i') ?? '—' }}
              </td>

              <td class="px-6 py-4 text-right space-x-2">
                <a href="{{ route('categoria-ingresos.show', $cat) }}"
                   class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                  {{ __('Ver') }}
                </a>

                <a href="{{ route('categoria-ingresos.edit', $cat) }}"
                   class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300">
                  {{ __('Editar') }}
                </a>

                <form action="{{ route('categoria-ingresos.destroy', $cat) }}" method="POST" class="inline">
                  @csrf @method('DELETE')
                  <button type="button"
                          class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 btn-delete"
                          data-id="{{ $cat->id_cat_ing }}">
                    {{ __('Eliminar') }}
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                {{ __('No hay categorías registradas.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="px-6 py-3 text-right bg-gray-50 dark:bg-gray-700">
        {{ $categorias->links() }}
      </div>
    </div>
  </div>

  {{-- SweetAlert2 para confirmar borrado --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- Alpine helpers --}}
  <script>
    function catIngresosIndexPage() {
      return {
        search: @json(request('search','')),
        loading: false,
        container: null,

        init() {
          this.container = document.getElementById('cat-ingresos-results');

          // Paginación AJAX (solo enlaces con ?page=)
          this.container?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            if (!/([?&])page=/.test(a.href)) return; // no interferir con Ver/Editar
            e.preventDefault();
            this.fetchTo(a.href);
          });

          // Delegación: confirmar borrado (funciona tras cada refresco)
          this.container?.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-delete');
            if (!btn) return;
            e.preventDefault();
            Swal.fire({
              title: '{{ __('¿Eliminar categoría?') }}',
              text: '{{ __('Esta acción no se puede deshacer.') }}',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: '{{ __('Sí, eliminar') }}',
              cancelButtonText: '{{ __('Cancelar') }}',
            }).then(r => {
              if (r.isConfirmed) btn.closest('form').submit();
            });
          });
        },

        buildUrl() {
          const base = @json(route('categoria-ingresos.index'));
          const params = new URLSearchParams({
            search: this.search ?? '',
            ajax: '1', // si el backend regresa solo el fragmento; si no, parseamos
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

            // Si viene página completa, extraemos solo el contenedor
            let htmlToInject = text;
            try {
              const doc  = new DOMParser().parseFromString(text, 'text/html');
              const frag = doc.querySelector('#cat-ingresos-results');
              if (frag) htmlToInject = frag.innerHTML;
            } catch (_) {}

            if (this.container) {
              this.container.innerHTML = htmlToInject;
              if (window.Alpine && Alpine.initTree) Alpine.initTree(this.container);
            }

            // Actualiza URL visible quitando ajax=1
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
