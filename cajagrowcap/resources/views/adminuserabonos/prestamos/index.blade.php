<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Préstamos de :name', ['name' => $cliente->nombre . ' ' . $cliente->apellido]) }}
    </h2>
  </x-slot>

  <style>[x-cloak]{ display:none!important; }</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="prestamosClientePage()">

    {{-- Búsqueda --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
      <div class="relative w-full sm:max-w-md">
        <input
          type="text"
          x-model="search"
          @input.debounce.400ms="liveSearch()"
          @keydown.enter.prevent
          placeholder="{{ __('Buscar por ID, período, monto, interés, fecha, status…') }}"
          class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500
                 bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"
        />
        {{-- spinner --}}
        <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto" id="prestamos-results">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 table-auto">
        <thead class="bg-green-600 dark:bg-green-800">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Período</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Monto</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Interés</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @foreach($prestamos as $p)
            <tr>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ str_pad($p->id, 3, '0', STR_PAD_LEFT) }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $p->tipo_prestamo }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                ${{ number_format($p->cantidad, 2) }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $p->interes }}%
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ \Carbon\Carbon::parse($p->fecha_solicitud)->format('Y-m-d') }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                @php
                  $labels = [
                    1 => 'Autorizado',
                    2 => 'Pendiente',
                    3 => 'En revisión',
                    4 => 'Rechazado',
                    5 => 'Pagado',
                    6 => 'Terminado',
                  ];
                @endphp
                {{ $labels[$p->status] ?? $p->status }}
              </td>
              <td class="px-6 py-4 text-right">
                <a
                  href="{{ route('adminuserabonos.abonos.index', $p->id) }}"
                  class="inline-flex items-center px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md transition"
                >
                  <svg xmlns="http://www.w3.org/2000/svg"
                       class="h-4 w-4 mr-1"
                       fill="none" viewBox="0 0 24 24"
                       stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5l7 7-7 7" />
                  </svg>
                  {{ __('Ver Detalles') }}
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $prestamos->links() }}
      </div>
    </div>
  </div>

  {{-- Alpine helpers --}}
  <script>
    function prestamosClientePage() {
      return {
        search: @json($search ?? ''), // si tu controlador pasa $search, se toma; si no, queda vacío
        loading: false,
        container: null,

        init() {
          this.container = document.getElementById('prestamos-results');

          // Paginación AJAX
          this.container?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            e.preventDefault();
            this.fetchTo(a.href);
          });
        },

        buildUrl() {
          // Ruta a esta misma vista (índice de préstamos por cliente)
          const base = @json(route('adminuserabonos.prestamos.index', $cliente->id));
          const params = new URLSearchParams({
            search: this.search ?? '',
            ajax: '1', // si el backend soporta ajax; si no, igual extraemos el fragmento
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

            // Si viene la página completa, extraemos #prestamos-results
            let htmlToInject = text;
            try {
              const doc = new DOMParser().parseFromString(text, 'text/html');
              const frag = doc.querySelector('#prestamos-results');
              if (frag) htmlToInject = frag.innerHTML;
            } catch (_) {}

            if (this.container) {
              this.container.innerHTML = htmlToInject;
              if (window.Alpine && Alpine.initTree) Alpine.initTree(this.container);
            }

            // Actualiza URL visible (quita ajax=1)
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
