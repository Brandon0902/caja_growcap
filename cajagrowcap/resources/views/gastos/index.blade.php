<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Transacciones entre cajas') }}
    </h2>
  </x-slot>

  <style>[x-cloak]{ display:none!important; }</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" x-data="gastosIndexPage()">
    {{-- + Nueva transacción + Buscador --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <a href="{{ route('gastos.create') }}"
         class="inline-flex items-center px-4 py-2
                bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-700 dark:hover:bg-yellow-800
                text-white font-semibold rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
        + {{ __('Nueva transacción') }}
      </a>

      <div class="relative w-full sm:max-w-md">
        <input type="text"
               x-model="search"
               @input.debounce.400ms="liveSearch()"
               @keydown.enter.prevent
               placeholder="{{ __('Buscar por tipo/caja/concepto/monto…') }}"
               class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500
                      bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" />
        <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>
    </div>

    {{-- Mensaje de éxito (texto viene del controlador) --}}
    @if(session('success'))
      <div class="mb-4 rounded-lg bg-yellow-100 p-4 text-yellow-800
                  dark:bg-yellow-900 dark:text-yellow-200">
        {{ session('success') }}
      </div>
    @endif

    {{-- Tabla --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg" id="gastos-results">
      <div class="overflow-x-auto">
        <table class="table-auto min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-purple-700 dark:bg-purple-900">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Transacción</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Caja Origen</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Caja Destino</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Monto</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Concepto</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($gastos as $gasto)
              <tr class="last:border-0">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $gasto->tipo }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ optional($gasto->cajaOrigen)->nombre ?? '-' }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ optional($gasto->cajaDestino)->nombre ?? '-' }}</td>
                <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-200">{{ number_format($gasto->cantidad, 2) }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $gasto->concepto }}</td>
                <td class="px-6 py-4 text-center space-x-3">
                  <a href="{{ route('gastos.show', $gasto) }}"
                     class="text-purple-600 hover:text-purple-800 dark:text-purple-300 dark:hover:text-purple-100">
                    {{ __('Ver') }}
                  </a>
                  <a href="{{ route('gastos.edit', $gasto) }}"
                     class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-300 dark:hover:text-yellow-100">
                    {{ __('Editar') }}
                  </a>
                  @if($gasto->comprobante)
                    <a href="{{ route('gastos.comprobante', $gasto) }}" target="_blank"
                       class="text-blue-600 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-100">
                      {{ __('Comprobante') }}
                    </a>
                  @endif
                  <form action="{{ route('gastos.destroy', $gasto) }}" method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="button"
                            class="btn-delete text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-600">
                      {{ __('Eliminar') }}
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6"
                    class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  {{ __('No hay transacciones registradas.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $gastos->links() }}
      </div>
    </div>
  </div>

  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- Alpine helpers --}}
  <script>
    function gastosIndexPage() {
      return {
        search: @json(request('search','')),
        loading: false,
        container: null,

        init() {
          this.container = document.getElementById('gastos-results');

          // Paginación AJAX (solo ?page=)
          this.container?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            if (!/([?&])page=/.test(a.href)) return;
            e.preventDefault();
            this.fetchTo(a.href);
          });

          // Eliminar con confirmación
          this.container?.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-delete');
            if (!btn) return;
            e.preventDefault();
            const form = btn.closest('form');
            Swal.fire({
              title: '{{ __('¿Eliminar transacción?') }}',
              text: '{{ __('Esta acción no se puede deshacer.') }}',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: '{{ __('Sí, eliminar') }}',
              cancelButtonText: '{{ __('Cancelar') }}',
            }).then(r => { if (r.isConfirmed) form.submit(); });
          });
        },

        buildUrl() {
          const base = @json(route('gastos.index'));
          const params = new URLSearchParams({
            search: this.search ?? '',
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
              const frag = doc.querySelector('#gastos-results');
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
