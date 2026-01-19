<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Abonos (Todos)') }}
    </h2>
  </x-slot>

  {{-- Necesario para ocultar elementos con x-cloak hasta que Alpine cargue --}}
  <style>[x-cloak]{ display: none !important; }</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="abonosGeneralPage()">

    {{-- Filtros --}}
    <div class="mb-4 grid grid-cols-1 md:grid-cols-6 gap-3">
      <div class="relative">
        <input type="text" x-model="search"
               @input.debounce.400ms="liveSearch()"
               @keydown.enter.prevent
               placeholder="{{ __('Buscar por cliente/email/ID/importe…') }}"
               class="w-full px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>
        {{-- spinner --}}
        <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>

      <select x-model="status" @change="liveSearch()"
              class="px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
        @foreach($statusOptions as $v => $label)
          <option value="{{ $v }}" @selected((string)$status === (string)$v)>{{ $label }}</option>
        @endforeach
      </select>

      <input type="date" x-model="desde" @change="liveSearch()"
             class="px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>
      <input type="date" x-model="hasta" @change="liveSearch()"
             class="px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>

      <select x-model="orden" @change="liveSearch()"
              class="px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
        <option value="recientes">{{ __('Más recientes') }}</option>
        <option value="antiguos">{{ __('Más antiguos') }}</option>
        <option value="monto_desc">{{ __('Monto ↓') }}</option>
        <option value="monto_asc">{{ __('Monto ↑') }}</option>
      </select>

      <button
        @click="aplicarFiltros()"
        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">
        {{ __('Filtrar') }}
      </button>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto" id="abonos-results">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 table-auto whitespace-nowrap">
        <thead class="bg-green-700 dark:bg-green-900">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">#</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Cliente') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Préstamo') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('# Pago') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Cantidad') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Fecha') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Vence') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Mora') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Saldo') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Status') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase">{{ __('Acciones') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($abonos as $a)
            @php
              // relación corregida
              $cliente = optional(optional($a->userPrestamo)->cliente);
            @endphp
            <tr>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $a->id }}</td>

              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                {{ $cliente?->nombre }} {{ $cliente?->apellido }}
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $cliente?->email }}</div>
              </td>

              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">#{{ $a->user_prestamo_id }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $a->num_pago }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">${{ number_format((float)$a->cantidad, 2) }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ optional($a->fecha)->format('Y-m-d') }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ optional($a->fecha_vencimiento)->format('Y-m-d') }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">${{ number_format((float)$a->mora_generada, 2) }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">${{ number_format((float)$a->saldo_restante, 2) }}</td>

              <td class="px-4 py-3">
                <form action="{{ route('adminuserabonos.abonos.updateStatus', $a->id) }}" method="POST">
                  @csrf
                  <select name="status" onchange="this.form.submit()"
                          class="border-gray-300 rounded dark:bg-gray-700 dark:text-gray-200">
                    <option value="0" @selected($a->status==0)>Pendiente</option>
                    <option value="1" @selected($a->status==1)>Pagado</option>
                    <option value="2" @selected($a->status==2)>Vencido</option>
                  </select>
                </form>
              </td>

              <td class="px-4 py-3 text-right">
                {{-- Botón que abre el modal (no navega) --}}
                <button
                  @click="abrirModalEditar({{ $a->id }})"
                  class="inline-flex items-center px-3 py-2 bg-indigo-500 hover:bg-indigo-600 text-white text-sm rounded-md">
                  {{ __('Editar') }}
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                {{ __('No se encontraron abonos.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-right">
        {{ $abonos->links() }}
      </div>
    </div>

    {{-- ===== Modal de edición (contenedor) ===== --}}
    <div
      x-cloak
      x-show="showEdit"
      x-transition.opacity
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @keydown.escape.window="cerrarModal()"
    >
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl relative">
        <button @click="cerrarModal()"
                class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white">
          ✕
        </button>

        {{-- Aquí inyectamos el HTML del partial edit_modal --}}
        <div class="p-4" x-html="editHtml"></div>
      </div>
    </div>

  </div>

  {{-- ====== Alpine helpers ====== --}}
  <script>
    function abonosGeneralPage() {
      return {
        // estado filtros (inicializado con valores del servidor)
        search: @json($search ?? ''),
        status: @json((string)($status ?? '')),
        desde:  @json($desde  ?? ''),
        hasta:  @json($hasta  ?? ''),
        orden:  @json($orden  ?? 'recientes'),

        // ui
        loading: false,

        // modal
        showEdit: false,
        editHtml: '',

        // refs
        container: null,

        init() {
          this.container = document.getElementById('abonos-results');

          // interceptar paginación / links dentro de resultados para hacerlos AJAX
          this.container?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            e.preventDefault();
            this.fetchTo(a.href);
          });
        },

        aplicarFiltros() {
          // navegación dura (fallback)
          const base = @json(route('adminuserabonos.abonos.general'));
          const params = new URLSearchParams({
            search: this.search ?? '',
            status: this.status ?? '',
            desde:  this.desde  ?? '',
            hasta:  this.hasta  ?? '',
            orden:  this.orden  ?? 'recientes',
          });
          window.location = `${base}?${params.toString()}`;
        },

        // ===== Live search & ajax refresh =====
        buildUrl() {
          const base = @json(route('adminuserabonos.abonos.general'));
          const params = new URLSearchParams({
            search: this.search ?? '',
            status: this.status ?? '',
            desde:  this.desde  ?? '',
            hasta:  this.hasta  ?? '',
            orden:  this.orden  ?? 'recientes',
            ajax:   '1', // si tu controlador soporta ajax, úsalo; si no, igual funcionará (ver parse)
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

            // Si el backend devuelve solo el fragmento, úsalo directo; si devuelve la página completa, extraemos #abonos-results
            let htmlToInject = text;
            try {
              const doc = new DOMParser().parseFromString(text, 'text/html');
              const frag = doc.querySelector('#abonos-results');
              if (frag) htmlToInject = frag.innerHTML; // solo el interior del contenedor
            } catch (_) {}

            if (this.container) {
              this.container.innerHTML = htmlToInject;

              // Reinit Alpine en lo inyectado (por si hay x-data en filas/modales)
              if (window.Alpine && Alpine.initTree) Alpine.initTree(this.container);
            }

            // Actualiza URL visible sin ajax=1
            const u = new URL(url, window.location.origin);
            u.searchParams.delete('ajax');
            history.replaceState({}, '', u);

          } catch (e) {
            console.error('Live search error:', e);
          } finally {
            this.loading = false;
          }
        },

        // ===== Modal =====
        async abrirModalEditar(id) {
          try {
            const url = @json(route('adminuserabonos.abonos.edit', '__ID__')).replace('__ID__', id);
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            const html = await res.text();
            this.editHtml = html;
            this.showEdit = true;
          } catch (e) {
            console.error(e);
            alert('No se pudo cargar el formulario de edición.');
          }
        },

        cerrarModal() {
          this.showEdit = false;
          this.editHtml = '';
        }
      }
    }
  </script>
</x-app-layout>
