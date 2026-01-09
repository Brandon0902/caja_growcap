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
       x-data="ticketsIndexPage()">

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

    {{-- Contenedor reemplazable por AJAX --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto" id="tickets-results">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
        <thead class="bg-purple-700 dark:bg-purple-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">#</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Cliente') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Asunto') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Categoría') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Prioridad') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Estado') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Creado') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">{{ __('Acciones') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($tickets as $t)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">#{{ $t->id }}</td>

              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ optional($t->cliente)->nombre ?? optional($t->usuario)->name ?? '—' }}
                @php $email = optional($t->cliente)->email ?? optional($t->usuario)->email; @endphp
                @if($email)
                  <div class="text-xs text-gray-500 dark:text-gray-400">{{ $email }}</div>
                @endif
              </td>

              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $t->asunto ?? $t->titulo ?? '—' }}
              </td>

              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ optional($t->categoria)->nombre ?? '—' }}
              </td>

              {{-- Prioridad --}}
              @php
                $prio = strtolower((string)($t->prioridad ?? ''));
                $prioMap = [
                  'baja' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                  'media'=> 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                  'alta' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                  'urgente'=>'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                ];
                $prioLabel = $t->prioridad_label ?? ucfirst($prio ?: '—');
                $prioClass = $prioMap[$prio] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
              @endphp
              <td class="px-6 py-4">
                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $prioClass }}">
                  {{ $prioLabel }}
                </span>
              </td>

              {{-- Estado --}}
              @php
                $est = strtolower((string)($t->estado ?? ''));
                $estMap = [
                  'abierto'  => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                  'progreso' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                  'resuelto' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
                  'cerrado'  => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                ];
                $estLabel = $t->estado_label ?? ucfirst($est ?: '—');
                $estClass = $estMap[$est] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
              @endphp
              <td class="px-6 py-4">
                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $estClass }}">
                  {{ $estLabel }}
                </span>
              </td>

              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ optional($t->created_at)->format('Y-m-d H:i') ?? '—' }}
              </td>

              <td class="px-6 py-4 text-right space-x-2">
                <a href="{{ route('tickets.show', $t) }}"
                   class="inline-flex items-center px-2 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-xs">
                  {{ __('Ver') }}
                </a>
                <a href="{{ route('tickets.edit', $t) }}"
                   class="inline-flex items-center px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs">
                  {{ __('Editar') }}
                </a>
                <form action="{{ route('tickets.destroy', $t) }}" method="POST" class="inline">
                  @csrf @method('DELETE')
                  <button type="button"
                          class="inline-flex items-center px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs btn-delete"
                          data-id="{{ $t->id }}">
                    {{ __('Eliminar') }}
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                {{ __('No hay tickets registrados.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-right">
        {{ $tickets->appends(['search' => request('search'), 'estado' => request('estado'), 'prioridad' => request('prioridad')])->links() }}
      </div>
    </div>
  </div>

  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- Alpine helpers --}}
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

          // Paginación AJAX (solo intercepta enlaces con ?page= dentro del contenedor)
          this.container?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a || !a.href) return;
            if (!/([?&])page=/.test(a.href)) return; // no interceptar ver/editar
            e.preventDefault();
            this.fetchTo(a.href);
          });

          // Delegación: eliminar con SweetAlert (permanece tras refrescos)
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

            // Si viene layout completo, extrae solo el fragmento
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
