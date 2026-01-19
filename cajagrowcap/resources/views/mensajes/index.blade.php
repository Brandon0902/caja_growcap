{{-- resources/views/mensajes/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      Mensajes
    </h2>
  </x-slot>

  <style>[x-cloak]{ display:none!important; }</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" x-data="mensajesIndexPage()">
    {{-- Alertas --}}
    @if(session('success'))
      <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-100">
        {{ session('success') }}
      </div>
    @endif
    @if($errors->any())
      <div class="mb-4 rounded-lg bg-rose-50 p-3 text-rose-800 dark:bg-rose-900/40 dark:text-rose-100">
        <ul class="list-disc list-inside">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
      <a href="{{ route('mensajes.create') }}"
         class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm">
        Nuevo Mensaje
      </a>

      {{-- Buscador dinámico (opcional si implementas en backend) --}}
      <div class="relative w-full sm:max-w-md">
        <input type="text"
               x-model="search"
               @input.debounce.400ms="liveSearch()"
               @keydown.enter.prevent
               placeholder="{{ __('Buscar por destinatario/asunto…') }}"
               class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500
                      bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>
        <svg x-show="loading" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg" id="mensajes-results">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-green-600 dark:bg-green-800">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Destinatario</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Asunto</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha Envío</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Estado</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($mensajes as $m)
              <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $m->id }}</td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $m->id_cliente
                      ? trim((optional($m->cliente)->nombre).' '.(optional($m->cliente)->apellido))
                      : 'Todos los clientes' }}
                </td>

                {{-- ASUNTO = nombre --}}
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $m->nombre ?? '—' }}
                </td>

                {{-- FECHA = fecha (datetime) --}}
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ $m->fecha ? \Carbon\Carbon::parse($m->fecha)->format('Y-m-d H:i') : '—' }}
                </td>

                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  @if((int)$m->status === 1)
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                      Activo
                    </span>
                  @else
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                      Inactivo
                    </span>
                  @endif
                </td>

                <td class="px-6 py-4 text-right space-x-1">
                  <a href="{{ route('mensajes.show',$m) }}"
                     class="inline-flex px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded">
                    Ver
                  </a>
                  <a href="{{ route('mensajes.edit',$m) }}"
                     class="inline-flex px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium rounded">
                    Editar
                  </a>
                  <form action="{{ route('mensajes.destroy',$m) }}" method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="button"
                            class="btn-delete px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded">
                      Borrar
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  No hay mensajes registrados.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $mensajes->links() }}
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    function mensajesIndexPage() {
      return {
        search: @json(request('search','')),
        loading: false,
        container: null,
        init() {
          this.container = document.getElementById('mensajes-results');

          // Paginación AJAX
          this.container?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            if (!/([?&])page=/.test(a.href)) return; // no interceptar Ver/Editar
            e.preventDefault();
            this.fetchTo(a.href);
          });

          // Confirmar borrado
          this.container?.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-delete');
            if (!btn) return;
            e.preventDefault();
            const form = btn.closest('form');
            Swal.fire({
              title: '¿Eliminar mensaje?',
              text: 'Esta acción no se puede deshacer.',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Sí, eliminar',
              cancelButtonText: 'Cancelar',
            }).then(r => { if (r.isConfirmed) form.submit(); });
          });
        },
        buildUrl() {
          const base = @json(route('mensajes.index'));
          const params = new URLSearchParams({ search: this.search ?? '', ajax: '1' });
          return `${base}?${params.toString()}`;
        },
        async liveSearch() { await this.fetchTo(this.buildUrl()); },
        async fetchTo(url) {
          this.loading = true;
          try {
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const text = await res.text();
            let htmlToInject = text;
            try {
              const doc  = new DOMParser().parseFromString(text, 'text/html');
              const frag = doc.querySelector('#mensajes-results');
              if (frag) htmlToInject = frag.innerHTML;
            } catch(_) {}
            if (this.container) {
              this.container.innerHTML = htmlToInject;
              if (window.Alpine && Alpine.initTree) Alpine.initTree(this.container);
            }
            const u = new URL(url, window.location.origin);
            u.searchParams.delete('ajax');
            history.replaceState({}, '', u);
          } finally { this.loading = false; }
        },
      }
    }
  </script>
</x-app-layout>
