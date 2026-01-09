{{-- resources/views/cuentas_por_pagar/detalles/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
      {{ __('Abonos General') }}
    </h2>
  </x-slot>

  <style>[x-cloak]{display:none!important}</style>

  <div
    id="abonos-frag"
    class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
    x-data="{
      showFilters:false,
      f:{
        estado: @js(request('estado','')),
        sucursal_id: @js(request('sucursal_id','')),
        desde: @js(request('desde','')),
        hasta: @js(request('hasta','')),
        q: @js(request('q',''))
      },
      buildUrl(){
        const u = new URL(@js(route('cuentas-por-pagar.abonos.index')), window.location.origin);
        if(this.f.q)           u.searchParams.set('q', this.f.q);
        if(this.f.estado)      u.searchParams.set('estado', this.f.estado);
        if(this.f.sucursal_id) u.searchParams.set('sucursal_id', this.f.sucursal_id);
        if(this.f.desde)       u.searchParams.set('desde', this.f.desde);
        if(this.f.hasta)       u.searchParams.set('hasta', this.f.hasta);
        return u.toString();
      },
      async apply(){
        if (window.__abonosApply) {
          await window.__abonosApply(this.f); // recarga vía AJAX sin salir de la pestaña
          this.showFilters = false;
        } else {
          window.location.href = this.buildUrl();
        }
      },
      async clear(){
        this.f = {estado:'', sucursal_id:'', desde:'', hasta:'', q:''};
        await this.apply();
      }
    }"
  >

    {{-- Barra con búsqueda y botón Filtros --}}
    <div class="flex items-center gap-2 mb-4">
      <div class="relative" x-data="{loading:false, async do(){ loading=true; try{ await $root.apply(); } finally{ loading=false; }}}">
        <input type="text"
               x-model="$root.f.q"
               @keydown.enter.prevent="do()"
               placeholder="Buscar por sucursal/proveedor…"
               class="w-72 rounded-md border px-3 py-2 text-sm pr-9 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"/>
        <svg x-show="loading" class="h-5 w-5 absolute right-2 top-2.5 animate-spin opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>

      <button
        class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm dark:border-gray-700 dark:text-gray-100"
        @click="showFilters=true"
        title="Filtros"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M10 12h4M12 16h0" />
        </svg>
        Filtros
      </button>
    </div>

    {{-- KPIs --}}
    @php
      $abonosCount   = (int)($resumen['abonos'] ?? $resumen['cuentas'] ?? 0);
      $saldoVencido  = (float)($resumen['saldo_vencido'] ?? 0);
      $saldoRestante = (float)($resumen['abonos_por_pagar'] ?? 0);
      $montoPagado   = (float)($resumen['monto_pagado'] ?? 0);
    @endphp

    <div class="mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="border bg-white dark:bg-gray-900 dark:border-gray-800 rounded-xl p-4">
        <div class="text-sm text-gray-600 dark:text-gray-300">Abonos</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($abonosCount) }}</div>
      </div>
      <div class="border bg-white dark:bg-gray-900 dark:border-gray-800 rounded-xl p-4">
        <div class="text-sm text-gray-600 dark:text-gray-300">Saldo vencido</div>
        <div class="mt-1 text-2xl font-semibold text-orange-600">${{ number_format($saldoVencido, 2) }}</div>
      </div>
      <div class="border bg-white dark:bg-gray-900 dark:border-gray-800 rounded-xl p-4">
        <div class="text-sm text-gray-600 dark:text-gray-300">Saldo Restante</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">${{ number_format($saldoRestante, 2) }}</div>
      </div>
      <div class="border bg-white dark:bg-gray-900 dark:border-gray-800 rounded-xl p-4">
        <div class="text-sm text-gray-600 dark:text-gray-300">Monto Pagado</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">${{ number_format($montoPagado, 2) }}</div>
      </div>
    </div>

    {{-- Tabla --}}
    <div id="cppd-results">
      <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="bg-gray-50 dark:bg-gray-900/40 text-gray-700 dark:text-gray-300">
              <th class="px-3 py-2 text-left">Cuenta</th>
              <th class="px-3 py-2 text-left">Abono</th>
              <th class="px-3 py-2 text-left">Estado</th>
              <th class="px-3 py-2 text-left">Comentarios</th>
              <th class="px-3 py-2 text-left">Acciones</th>
            </tr>
          </thead>
          <tbody>
          @forelse($detalles as $d)
            @php
              $paidAt = $d->estado === 'pagado' ? optional($d->updated_at)->format('d M Y H:i') : null;
              $rowStateClass = match($d->estado){
                'vencido' => 'bg-red-50/40 dark:bg-red-900/10 border-l-4 border-red-500',
                'pagado'  => 'bg-emerald-50/40 dark:bg-emerald-900/10 border-l-4 border-emerald-500',
                default   => 'bg-white dark:bg-gray-800 border-l-4 border-yellow-400',
              };
              $badgeClass = match($d->estado){
                'vencido' => 'bg-red-100 text-red-800',
                'pagado'  => 'bg-emerald-100 text-emerald-800',
                default   => 'bg-yellow-100 text-yellow-800',
              };
            @endphp

            <tr class="border-t border-gray-200 dark:border-gray-700 align-top {{ $rowStateClass }}">
              {{-- CUENTA --}}
              <td class="px-3 py-2">
                <div class="font-semibold text-gray-900 dark:text-gray-100">
                  {{ $d->cuenta?->proveedor?->nombre ?? '—' }}
                  <span class="text-sm text-gray-500 dark:text-gray-400">(#{{ $d->cuenta?->id_cuentas_por_pagar ?? '—' }})</span>
                </div>
                @if($d->cuenta?->descripcion)
                  <div class="text-sm text-gray-700 dark:text-gray-200">
                    {{ $d->cuenta->descripcion }}
                  </div>
                @endif
                @if(!is_null($d->cuenta?->monto_total))
                  <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Total cuenta: ${{ number_format($d->cuenta->monto_total,2) }}</div>
                @endif
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Sucursal: {{ $d->cuenta?->sucursal?->nombre ?? '—' }}
                </div>
              </td>

              {{-- ABONO --}}
              <td class="px-3 py-2">
                <div class="font-semibold text-gray-900 dark:text-gray-100">
                  ${{ number_format($d->monto_pago, 2) }}
                </div>
              </td>

              {{-- ESTADO + DETALLES --}}
              <td class="px-3 py-2">
                <div class="mb-1">
                  <span class="px-2 py-0.5 rounded text-xs {{ $badgeClass }}">
                    {{ ucfirst($d->estado) }}
                  </span>
                </div>

                <div class="space-y-0.5 text-xs text-gray-600 dark:text-gray-300">
                  <div>Pago: {{ $d->numero_pago }} @if($d->semana) — Semana {{ $d->semana }} @endif</div>
                  <div>Programado: {{ \Carbon\Carbon::parse($d->fecha_pago)->format('d M Y') }}</div>
                  <div>Saldo restante: ${{ number_format($d->saldo_restante, 2) }}</div>
                  @if($paidAt)
                    <div class="text-emerald-700 dark:text-emerald-400">Pagado el {{ $paidAt }}</div>
                  @endif
                  @if($d->caja?->nombre)
                    <div>Caja: {{ $d->caja->nombre }}</div>
                  @endif
                </div>
              </td>

              {{-- COMENTARIOS --}}
              <td class="px-3 py-2">{{ $d->comentario ?: '—' }}</td>

              {{-- ACCIONES --}}
              <td class="px-3 py-2">
                <div class="flex items-center gap-3">
                  @if($d->cuenta)
                    <a class="text-indigo-600 hover:underline" href="{{ route('cuentas-por-pagar.show', $d->cuenta) }}">Ver</a>
                  @endif

                  {{-- 🔔 Botón Pagar: usa el MISMO modal global mediante evento cpp-open-pay --}}
                  <button
                    type="button"
                    class="text-green-700 hover:underline"
                    @click="
                      window.dispatchEvent(new CustomEvent('cpp-open-pay', {
                        detail: {
                          tipo: 'abono',
                          detalleId: {{ $d->id }},
                          cuentaId: {{ $d->cuenta_id }},
                          montoAbono: {{ (float) $d->monto_pago }}
                        }
                      }))
                    "
                    {{ $d->estado === 'pagado' ? 'disabled' : '' }}
                  >
                    Pagar
                  </button>

                  @if(Route::has('cuentas-por-pagar.detalles.edit'))
                    <a class="text-blue-600 hover:underline" href="{{ route('cuentas-por-pagar.detalles.edit', $d) }}">Editar</a>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-3 py-6 text-center text-gray-500">No hay abonos para los filtros seleccionados.</td>
            </tr>
          @endforelse
          </tbody>
        </table>

        <div class="mt-4">
          {{ $detalles->withQueryString()->links() }}
        </div>

        {{-- ✅ Se eliminaron los modales por fila.
             Ahora todo se resuelve con el modal global (teleport en index). --}}
      </div>
    </div>

    {{-- OFF-CANVAS: FILTROS --}}
    <div x-cloak x-show="showFilters" class="fixed inset-0 z-50" aria-modal="true" role="dialog">
      <div class="absolute inset-0 bg-black/40" @click="showFilters=false"></div>
      <div class="absolute right-0 top-0 h-full w-full max-w-sm bg-white dark:bg-gray-900 shadow-xl p-4">
        <div class="flex items-center justify-between mb-3">
          <div class="text-lg font-semibold">Filtros</div>
          <button @click="showFilters=false" class="text-gray-500">&times;</button>
        </div>

        <div class="space-y-3">
          <div>
            <label class="block text-sm mb-1">Estado</label>
            <select x-model="f.estado" class="w-full rounded-md dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
              <option value="">— Todos —</option>
              <option value="pendiente">Pendiente</option>
              <option value="pagado">Pagado</option>
              <option value="vencido">Vencido</option>
            </select>
          </div>

          <div>
            <label class="block text-sm mb-1">Sucursal</label>
            <select x-model="f.sucursal_id" class="w-full rounded-md dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
              <option value="">— Todas —</option>
              @foreach(\App\Models\Sucursal::orderBy('nombre')->get(['id_sucursal','nombre']) as $s)
                <option value="{{ $s->id_sucursal }}">{{ $s->nombre }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-sm mb-1">Desde</label>
            <input type="date" x-model="f.desde" class="w-full rounded-md dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
          </div>

          <div>
            <label class="block text-sm mb-1">Hasta</label>
            <input type="date" x-model="f.hasta" class="w-full rounded-md dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
          </div>
        </div>

        <div class="mt-6 flex justify-between">
          <button class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700" @click="clear()">Limpiar</button>
          <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700" @click="apply()">Filtrar</button>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
