<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Tipos de Préstamo') }}
    </h2>
  </x-slot>

  <style>[x-cloak]{display:none!important}</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{
         search: '{{ $search ?? '' }}',
         status: '{{ $status ?? '' }}',
         desde:  '{{ $desde  ?? '' }}',
         hasta:  '{{ $hasta  ?? '' }}',
         orden:  '{{ $orden  ?? 'fecha_desc' }}',
         typing: false,
         aplicarFiltros() {
           const base = '{{ route('prestamos.index') }}';
           const qs = new URLSearchParams({
             search: this.search ?? '',
             status: this.status ?? '',
             desde:  this.desde  ?? '',
             hasta:  this.hasta  ?? '',
             orden:  this.orden  ?? 'fecha_desc',
           });
           window.location = `${base}?${qs.toString()}`;
         },
         limpiar() {
           this.search = ''; this.status = ''; this.desde = ''; this.hasta = ''; this.orden = 'fecha_desc';
           this.aplicarFiltros();
         }
       }">

    @if(session('success'))
      <div class="mb-4 rounded bg-green-100 p-3 text-green-800 dark:bg-green-900 dark:text-green-200">
        {{ session('success') }}
      </div>
    @endif

    {{-- Crear --}}
    <div class="mb-4">
      <a href="{{ route('prestamos.create') }}"
         class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md shadow">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        {{ __('Nuevo tipo de préstamo') }}
      </a>
    </div>

    {{-- Filtros --}}
    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
      <div class="relative">
        <input type="text"
               x-model="search"
               @keydown.debounce.500ms="aplicarFiltros()"
               @keydown="typing=true; clearTimeout(window.__t); window.__t=setTimeout(()=>typing=false,600)"
               placeholder="{{ __('Buscar por periodo / semanas / interés / montos / antigüedad…') }}"
               class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500
                      bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"/>
        <svg x-cloak x-show="typing" class="h-5 w-5 animate-spin absolute right-2 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>

      <select x-model="status"
              class="px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
        @foreach($statusOptions as $key => $label)
          <option value="{{ $key }}" @selected((string)($status ?? '') === (string)($key ?? ''))>{{ $label }}</option>
        @endforeach
      </select>

      <input type="date" x-model="desde"
             class="px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" />
      <input type="date" x-model="hasta"
             class="px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600" />

      <select x-model="orden"
              class="px-3 py-2 border rounded-md bg-white dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
        <option value="fecha_desc">{{ __('Más recientes') }}</option>
        <option value="fecha_asc">{{ __('Más antiguos') }}</option>
        <option value="interes_desc">{{ __('Interés ↓') }}</option>
        <option value="interes_asc">{{ __('Interés ↑') }}</option>
        <option value="monto_maximo_desc">{{ __('Monto máx. ↓') }}</option>
        <option value="monto_maximo_asc">{{ __('Monto máx. ↑') }}</option>
      </select>

      <div class="flex gap-2">
        <button @click="aplicarFiltros()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md shadow">
          {{ __('Filtrar') }}
        </button>
        <button @click="limpiar()"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md shadow dark:bg-gray-700 dark:text-gray-200">
          {{ __('Limpiar') }}
        </button>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
        <thead class="bg-green-700 dark:bg-green-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">#</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Periodo</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Semanas</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">% Interés</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Monto mín.</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Monto máx.</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Antigüedad</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Estado</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
          </tr>
        </thead>

        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($prestamos as $p)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ str_pad($p->id_prestamo, 3, '0', STR_PAD_LEFT) }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $p->periodo }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $p->semanas }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ number_format($p->interes,2) }}%</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">${{ number_format($p->monto_minimo,2) }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">${{ number_format($p->monto_maximo,2) }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $p->antiguedad }}</td>

              {{-- Estado rápido: SOLO el select (sin etiqueta adicional) --}}
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                <form method="POST" action="{{ route('prestamos.quick-status', $p) }}">
                  @csrf
                  @method('PATCH')
                  <select name="status"
                          class="rounded-md border-gray-300 dark:bg-gray-700 dark:text-gray-100"
                          onchange="this.form.submit()">
                    <option value="1" @selected($p->status != '0')>Activo</option>
                    <option value="0" @selected($p->status == '0')>Inactivo</option>
                  </select>
                </form>
              </td>

              <td class="px-6 py-4 text-right">
                <div class="inline-flex items-center gap-2">
                  <a href="{{ route('prestamos.show', $p) }}"
                     class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm rounded-md">
                    Ver
                  </a>
                  <a href="{{ route('prestamos.edit', $p) }}"
                     class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-md">
                    Editar
                  </a>
                  <form action="{{ route('prestamos.destroy', $p) }}" method="POST"
                        onsubmit="return confirm('¿Inactivar este tipo de préstamo?');">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm rounded-md">
                      Eliminar
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                {{ __('No hay tipos de préstamo registrados.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="px-4 py-3 text-right bg-gray-50 dark:bg-gray-700 sm:px-6">
        {{ $prestamos->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
