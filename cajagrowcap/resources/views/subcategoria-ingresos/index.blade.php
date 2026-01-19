{{-- resources/views/subcategoria_ingresos/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Subcategorías de Ingreso') }}
    </h2>
  </x-slot>

  <style>[x-cloak]{display:none!important}</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="subIngIndexPage()">

    <div class="mb-4 flex justify-between items-center gap-3">
      <a href="{{ route('subcategoria-ingresos.create') }}"
         class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded">
        + {{ __('Nueva Subcategoría') }}
      </a>

      <div class="relative w-full sm:w-80">
        <input type="text"
               x-model="search"
               @input.debounce.400ms="liveSearch()"
               @keydown.enter.prevent
               placeholder="{{ __('Buscar…') }}"
               class="w-full px-3 py-2 border rounded shadow-sm focus:ring-2 focus:ring-purple-500
                      bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>
        {{-- Spinner --}}
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

    {{-- Contenedor reemplazable por AJAX --}}
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden" id="subing-results">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-purple-700 dark:bg-purple-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Nombre') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Categoría') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Creado por') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Fecha') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">{{ __('Acciones') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($subs as $s)
            <tr>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $s->nombre }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ optional($s->categoria)->nombre ?? '—' }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ optional($s->usuario)->name ?? '—' }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ optional($s->created_at)->format('Y-m-d H:i') ?? '—' }}
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <a href="{{ route('subcategoria-ingresos.show',$s) }}"
                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Ver</a>
                <a href="{{ route('subcategoria-ingresos.edit',$s) }}"
                   class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300">Editar</a>
                <form action="{{ route('subcategoria-ingresos.destroy',$s) }}" method="POST" class="inline">
                  @csrf @method('DELETE')
                  <button type="button"
                          class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 btn-delete"
                          data-id="{{ $s->id_sub_ing }}">
                    Eliminar
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                {{ __('No hay subcategorías registradas.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-right">
        {{ $subs->appends(['search' => request('search')])->links() }}
      </div>
    </div>
  </div>

  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- Alpine helpers --}}
  <script>
    function subIngIndexPage() {
      return {
        search: @json($search ?? request('search','')),
        loading: false,
        container: null,

        init() {
          this.container = document.getElementById('subing-results');

          // Paginación AJAX dentro del contenedor
          this.container?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            if (!/([?&])page=/.test(a.href)) return; // ignora links "ver/editar"
            e.preventDefault();
            this.fetchTo(a.href);
          });

          // Delegación para eliminar (sigue funcionando tras refrescos)
          this.container?.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-delete');
            if (!btn) return;
            e.preventDefault();
            const form = btn.closest('form');
            Swal.fire({
              title: '¿Eliminar subcategoría?',
              text: 'No se podrá deshacer.',
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
          const base = @json(route('subcategoria-ingresos.index'));
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

            // Si viene la página completa, extrae solo el fragmento
            let htmlToInject = text;
            try {
              const doc = new DOMParser().parseFromString(text, 'text/html');
              const frag = doc.querySelector('#subing-results');
              if (frag) htmlToInject = frag.innerHTML;
            } catch (_) {}

            if (this.container) {
              this.container.innerHTML = htmlToInject;
              if (window.Alpine && Alpine.initTree) Alpine.initTree(this.container);
            }

            // Actualiza URL visible (sin ajax=1)
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
