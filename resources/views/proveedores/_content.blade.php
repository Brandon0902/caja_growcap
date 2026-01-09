{{-- resources/views/proveedores/_content.blade.php --}}
@unless($panel ?? false)
 <x-slot name="header">
      <div class="flex items-center justify-start gap-3">
        @can('proveedores.crear')
        <a href="{{ route('proveedores.create') }}"
           class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700
                  text-white text-sm font-medium rounded-md shadow">
          + {{ __('Nuevo') }}
        </a>
        @endcan

        <h2 class="font-semibold text-xl text-white leading-tight">
          {{ __('Proveedores') }}
        </h2>
      </div>
    </x-slot>
@endunless

<style>[x-cloak]{ display:none!important; }</style>

<div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
     x-data="proveedoresIndexPage()">

  @if(session('success'))
    <div class="mb-4 rounded-lg bg-green-100 dark:bg-green-900 p-3 text-green-800 dark:text-green-200 text-sm">
      {{ session('success') }}
    </div>
  @endif

  {{-- Filtros --}}
  <div class="mb-4 flex flex-col lg:flex-row lg:items-center gap-3">
    <div class="relative flex-1">
      <input type="text"
             x-model="search"
             @input.debounce.400ms="liveSearch()"
             @keydown.enter.prevent
             placeholder="Buscar por nombre / email / teléfono / contacto / dirección"
             class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>
      {{-- spinner --}}
      <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
        <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
      </svg>
    </div>

    <select x-model="estado" @change="liveSearch()"
            class="px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
      <option value="">{{ __('Todos') }}</option>
      <option value="activo">{{ __('Activos') }}</option>
      <option value="inactivo">{{ __('Inactivos') }}</option>
    </select>

    <select x-model="orden" @change="liveSearch()"
            class="px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
      <option value="recientes">{{ __('Más recientes') }}</option>
      <option value="antiguos">{{ __('Más antiguos') }}</option>
      <option value="nombre_asc">{{ __('Nombre A–Z') }}</option>
      <option value="nombre_desc">{{ __('Nombre Z–A') }}</option>
    </select>

    <div class="flex gap-2">
      <button
        @click="irConParametros()"
        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md shadow">
        {{ __('Filtrar') }}
      </button>
    </div>
  </div>

  {{-- Contenedor reemplazable por AJAX --}}
  <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg" id="proveedores-results">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        <thead class="bg-purple-700 dark:bg-purple-900 text-xs">
          <tr>
            <th class="px-4 py-2 text-left font-medium text-white uppercase">#</th>
            <th class="px-4 py-2 text-left font-medium text-white uppercase">Nombre</th>
            <th class="px-4 py-2 text-left font-medium text-white uppercase">Email</th>
            <th class="px-4 py-2 text-left font-medium text-white uppercase">Teléfono</th>
            <th class="px-4 py-2 text-left font-medium text-white uppercase">Contacto</th>
            <th class="px-4 py-2 text-left font-medium text-white uppercase">Estado</th>
            <th class="px-4 py-2 text-right font-medium text-white uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($proveedores as $p)
            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
              <td class="px-4 py-2">{{ str_pad($p->id_proveedor, 3, '0', STR_PAD_LEFT) }}</td>
              <td class="px-4 py-2">
                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $p->nombre }}</div>
                @if($p->direccion)
                  <div class="text-xs text-gray-500 dark:text-gray-400">{{ $p->direccion }}</div>
                @endif
              </td>
              <td class="px-4 py-2">{{ $p->email ?: '—' }}</td>
              <td class="px-4 py-2">{{ $p->telefono ?: '—' }}</td>
              <td class="px-4 py-2">{{ $p->contacto ?: '—' }}</td>
              <td class="px-4 py-2">
                @if($p->estado === 'activo')
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Activo
                  </span>
                @else
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-800">
                    Inactivo
                  </span>
                @endif
              </td>
              <td class="px-4 py-2 text-right space-x-2">
                <a href="{{ route('proveedores.edit', $p->id_proveedor) }}"
                   class="px-2 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-xs">
                  Editar
                </a>

                <form action="{{ route('proveedores.toggle', $p->id_proveedor) }}" method="POST" class="inline">
                  @csrf @method('PATCH')
                  <button type="submit"
                          class="px-2 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded text-xs">
                    {{ $p->estado === 'activo' ? 'Inactivar' : 'Activar' }}
                  </button>
                </form>

                <form action="{{ route('proveedores.destroy', $p->id_proveedor) }}"
                      method="POST" class="inline">
                  @csrf @method('DELETE')
                  <button type="button"
                          class="btn-del px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs"
                          data-id="{{ $p->id_proveedor }}">
                    Eliminar
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                No hay proveedores registrados.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-right">
      {{ $proveedores->links() }}
    </div>
  </div>
</div>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- Alpine helpers --}}
<script>
  function proveedoresIndexPage() {
    return {
      search: @json($search ?? request('search','')),
      estado: @json($estado ?? request('estado','')),
      orden:  @json($orden  ?? request('orden','recientes')),
      loading: false,
      container: null,

      init() {
        this.container = document.getElementById('proveedores-results');

        // Paginación AJAX (solo enlaces con ?page= dentro del contenedor)
        this.container?.addEventListener('click', (e) => {
          const a = e.target.closest('a');
          if (!a || !a.href) return;
          if (!/([?&])page=/.test(a.href)) return; // no interferir con Editar/Nuevo
          e.preventDefault();
          this.fetchTo(a.href);
        });

        // Delegación: confirmar borrado
        this.container?.addEventListener('click', (e) => {
          const btn = e.target.closest('.btn-del');
          if (!btn) return;
          e.preventDefault();
          const form = btn.closest('form');
          Swal.fire({
            title: '¿Eliminar este proveedor?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa'
          }).then(r => {
            if (r.isConfirmed) form.submit();
          });
        });
      },

      buildUrl() {
        const base = @json(route('proveedores.index'));
        const params = new URLSearchParams({
          search: this.search ?? '',
          estado: this.estado ?? '',
          orden:  this.orden  ?? 'recientes',
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
          const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const text = await res.text();

          // Si viene la página completa, extraemos #proveedores-results
          let htmlToInject = text;
          try {
            const doc  = new DOMParser().parseFromString(text, 'text/html');
            const frag = doc.querySelector('#proveedores-results');
            if (frag) htmlToInject = frag.innerHTML;
          } catch (_) {}

          if (this.container) {
            this.container.innerHTML = htmlToInject;
            if (window.Alpine && Alpine.initTree) Alpine.initTree(this.container);
          }

          // Actualiza URL visible (quitando ajax=1)
          const u = new URL(url, window.location.origin);
          u.searchParams.delete('ajax');
          history.replaceState({}, '', u);

        } catch (e) {
          console.error('Live search error:', e);
        } finally {
          this.loading = false;
        }
      },

      irConParametros() {
        // Acción "Filtrar" tradicional por si se quiere refrescar todo
        const u = new URL(this.buildUrl(), window.location.origin);
        u.searchParams.delete('ajax');
        window.location = u.toString();
      }
    }
  }
</script>
