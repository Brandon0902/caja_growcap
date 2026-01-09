<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white">{{ __('Cuentas por pagar') }}</h2>
  </x-slot>

  <style>
    [x-cloak]{display:none!important}
    .kpi-card{border-radius:.75rem}
  </style>

  <div
    class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
    x-data="cppIndexPage()"
    x-init="init()"
    @cpp-open-pay.window="handleOpenPay($event.detail)"
  >
    @if(session('success'))
      <div class="mb-4 rounded-lg bg-green-100 dark:bg-green-900 p-3 text-green-800 dark:text-green-100 text-sm">
        {{ session('success') }}
      </div>
    @endif

    {{-- Pestañas --}}
    <div class="mb-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
      <button
        class="px-4 py-2 rounded-t-lg"
        :class="activeTab==='registro' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 border border-b-0 dark:border-gray-700' : 'text-gray-600 dark:text-gray-300'"
        @click="openTab('registro')"
      >Registro</button>

      <button
        class="px-4 py-2 rounded-t-lg"
        :class="activeTab==='abonos' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 border border-b-0 dark:border-gray-700' : 'text-gray-600 dark:text-gray-300'"
        @click="openTab('abonos')"
      >Abonos general</button>
    </div>

    {{-- ================= TAB: REGISTRO ================= --}}
    <div x-show="activeTab==='registro'">
      <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <a href="{{ route('cuentas-por-pagar.create') }}"
           class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" d="M12 5v14M5 12h14"/></svg>
          {{ __('Nueva Cuenta') }}
        </a>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 md:w-[42rem]">
          <select x-model="estado" @change="liveSearch()"
                  class="w-full rounded-md border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
            <option value="">{{ __('— Todos los Status —') }}</option>
            <option value="al_corriente">{{ __('Al corriente') }}</option>
            <option value="vencido">{{ __('Vencido') }}</option>
            <option value="pagado">{{ __('Pagado') }}</option>
          </select>

          <select x-model="sucursal" @change="liveSearch()"
                  class="w-full rounded-md border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100">
            <option value="">{{ __('— Todas las Sucursales —') }}</option>
            @foreach(($sucursales ?? []) as $s)
              <option value="{{ $s->id }}" @selected(request('sucursal')==$s->id)>{{ $s->nombre }}</option>
            @endforeach
          </select>

          <div class="relative">
            <input type="text" x-model="search" @input.debounce.400ms="liveSearch()" @keydown.enter.prevent
                   placeholder="{{ __('Buscar por sucursal/proveedor/caja/descripcion…') }}"
                   class="w-full rounded-md border px-3 py-2 text-sm pr-9 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100" />
            <svg x-show="loading" class="h-5 w-5 absolute right-2 top-2.5 animate-spin opacity-70" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
              <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
            </svg>
          </div>
        </div>
      </div>

      {{-- CONTENEDOR AJAX: KPIs + LISTA --}}
      <div id="cuentas-results">
        @php
          $collection   = $cuentas->getCollection();
          $totalCuentas = $cuentas->total();
          $saldoVencido = $collection->sum(fn($c)=> (float)($c->total_vencido ?? 0));
          $saldoRestante = $collection->sum(function($c){
            $monto  = (float)($c->monto_total ?? 0);
            $pagado = (float)($c->monto_pagado ?? 0);
            return $c->saldo_restante ?? max(0, $monto - $pagado);
          });
          $groupByLabel = fn($c)=> optional($c->fecha_emision)->translatedFormat('j \\de F');
        @endphp

        {{-- KPIs --}}
        <div class="mb-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
          <div class="kpi-card border bg-white p-4 shadow-sm dark:bg-gray-900 dark:border-gray-800">
            <div class="text-sm text-gray-600 dark:text-gray-300">{{ __('Cuentas') }}</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($totalCuentas) }}</div>
          </div>
          <div class="kpi-card border bg-white p-4 shadow-sm dark:bg-gray-900 dark:border-gray-800">
            <div class="text-sm text-gray-600 dark:text-gray-300">{{ __('Saldo vencido') }}</div>
            <div class="mt-1 text-2xl font-semibold text-orange-600">${{ number_format($saldoVencido, 2) }}</div>
          </div>
          <div class="kpi-card border bg-white p-4 shadow-sm dark:bg-gray-900 dark:border-gray-800">
            <div class="text-sm text-gray-600 dark:text-gray-300">{{ __('Saldo restante') }}</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">${{ number_format($saldoRestante, 2) }}</div>
          </div>
        </div>

        @php $grouped = $collection->groupBy($groupByLabel); @endphp

        <div class="rounded-lg border bg-white shadow-sm dark:bg-gray-900 dark:border-gray-800">
          @forelse($grouped as $fechaLabel => $items)
            <div class="border-b bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
              {{ $fechaLabel }}
            </div>

            @foreach($items as $cuenta)
              @php
                $proveedor = optional($cuenta->proveedor)->nombre ?? '—';
                $monto     = (float)($cuenta->monto_total ?? 0);
                $pagado    = (float)($cuenta->monto_pagado ?? 0);
                $restante  = $cuenta->saldo_restante ?? max(0, $monto - $pagado);
                $vencido   = (float)($cuenta->total_vencido ?? 0);
                $pct       = $monto > 0 ? round(($pagado / $monto) * 100) : 0;

                $cntT = (int)($cuenta->cnt_total ?? 0);
                $cntV = (int)($cuenta->cnt_vencidos ?? 0);
                $cntPg= (int)($cuenta->cnt_pagados ?? 0);

                if ($cntV > 0)                         $estadoCalc = 'vencido';
                elseif ($cntT > 0 && $cntPg === $cntT) $estadoCalc = 'pagado';
                elseif ($restante > 0)                 $estadoCalc = 'al_corriente';
                else                                   $estadoCalc = 'al_corriente';

                $estadoTxt   = ['pagado'=>'Pagado','vencido'=>'Vencido','al_corriente'=>'Al corriente'][$estadoCalc];
                $estadoClass = match($estadoCalc){ 'pagado'=>'text-green-600','vencido'=>'text-red-600', default=>'text-green-600'};
                $rowBg       = $estadoCalc==='pagado' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-white dark:bg-gray-900';
              @endphp

              <div class="px-4 py-4 {{ $rowBg }} border-b dark:border-gray-800">
                <div class="grid gap-4 lg:grid-cols-3">
                  <a href="{{ route('cuentas-por-pagar.show', $cuenta) }}"
                     class="min-w-0 order-1 block group focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md">
                    <div class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate group-hover:underline">
                      {{ $proveedor }}
                    </div>
                    <div class="mt-0.5 text-sm text-gray-700 dark:text-gray-300 truncate" title="{{ $cuenta->descripcion }}">
                      {{ \Illuminate\Support\Str::limit($cuenta->descripcion ?? '—', 120) }}
                    </div>
                    <div class="mt-1 flex items-center gap-3 text-sm">
                      <span class="font-semibold text-gray-900 dark:text-gray-100">${{ number_format($monto,2) }}</span>
                      <span class="text-blue-600">{{ $pct }}%</span>
                    </div>

                    <div class="mt-1 text-sm">
                      @if(($cuenta->cnt_vencidos ?? 0) > 0)
                        <span class="cursor-pointer underline {{ $estadoClass }}"
                              x-on:click.stop.prevent="$store.cpp.toggleVencidos({{ $cuenta->id_cuentas_por_pagar }})">
                          Vencido ({{ (int)($cuenta->cnt_vencidos ?? 0) }})
                        </span>
                      @else
                        <span class="{{ $estadoClass }}">{{ $estadoTxt }}</span>
                      @endif
                    </div>
                  </a>

                  <div class="order-2 self-center">
                    <div class="text-sm">
                      <div class="flex items-center justify-between gap-6">
                        <span class="text-gray-600 dark:text-gray-300">{{ __('Saldo restante') }}</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">${{ number_format($restante, 2) }}</span>
                      </div>
                      <div class="mt-1 flex items-center justify-between gap-6">
                        <span class="text-gray-600 dark:text-gray-300">{{ __('Saldo vencido') }}</span>
                        <span class="font-semibold {{ $vencido>0 ? 'text-red-600' : 'text-gray-900 dark:text-gray-100' }}">
                          ${{ number_format($vencido, 2) }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="order-3 justify-self-center self-center">
                    <div class="flex flex-row lg:flex-col items-center gap-2">
                      <a href="{{ route('cuentas-por-pagar.edit', $cuenta) }}"
                         class="inline-flex items-center justify-center rounded-full border p-2 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200"
                         title="{{ __('Editar') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-width="2" d="M12 20h9"/><path stroke-width="2" d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                        </svg>
                      </a>
                      <form action="{{ route('cuentas-por-pagar.destroy', $cuenta) }}" method="POST"
                            onsubmit="return confirm('{{ __('¿Seguro que quieres eliminarla?') }}');">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-full bg-red-600 p-2 text-white hover:bg-red-700"
                                title="{{ __('Eliminar') }}">
                          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-width="2" d="M3 6h18M8 6v14m8-14v14M5 6l1-3h12l1 3M10 10v10m4-10v10"/>
                          </svg>
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>

              {{-- Contenedor de vencidos --}}
              <div class="px-4 py-2 border-t dark:border-gray-800">
                <div id="vencidos-{{ $cuenta->id_cuentas_por_pagar }}" class="mt-2 hidden"></div>
              </div>
            @endforeach
          @empty
            <div class="px-4 py-10 text-center text-sm text-gray-600 dark:text-gray-300">
              {{ __('Sin resultados') }}
            </div>
          @endforelse

          <div class="px-4 py-3">
            {{ $cuentas->withQueryString()->links() }}
          </div>
        </div>
      </div>
    </div>

    {{-- ================= TAB: ABONOS GENERAL ================= --}}
    <div x-show="activeTab==='abonos'">
      <div id="abonos-content" class="min-h-[6rem]">
        <div class="text-sm text-gray-500">Cargando abonos…</div>
      </div>
    </div>
  </div>

  {{-- JS --}}
  <script>
    (function ensureCppStore(){
      function createStore(){
        if (!window.Alpine) return;
        if (window.__CPP_STORE_READY) return;
        if (Alpine.store('cpp')) { window.__CPP_STORE_READY = true; return; }

        Alpine.store('cpp', {
          modalOpen: false,
          tipo: 'abono',
          detalleId: null,
          cuentaId: null,
          montoAbono: 0,
          get montoAbonoFmt(){ return `$${Number(this.montoAbono).toLocaleString(undefined,{minimumFractionDigits:2})}`; },

          openPayModal({tipo='abono', detalleId=null, cuentaId=null, montoAbono=0}){
            this.tipo = tipo; this.detalleId = detalleId; this.cuentaId = cuentaId; this.montoAbono = montoAbono;
            this.modalOpen = true;
          },
          closeModal(){ this.modalOpen = false; },

          // 🔔 helper SweetAlert2
          notify({icon='success', title='', text='', html=null, timer=1700}={}){
            const opts = {
              icon, title, timer,
              showConfirmButton:false,
              toast:true, position:'top-end'
            };
            if (html) { opts.html = html; } else if (text) { opts.text = text; }
            return Swal.fire(opts);
          },

          async submitPago(form){
            if (!form.reportValidity()) return;
            const data = new FormData(form);
            
            data.append('_method', 'PATCH');

            try {
              let url, successText;
              if (this.tipo === 'total_vencido') {
                url = @json(route('cuentas-por-pagar.pagar-total', ['cuenta' => 'CUENTA_ID'])).replace('CUENTA_ID', this.cuentaId);
                successText = 'Se pagó el total vencido de la cuenta.';
              } else {
                url = @json(route('cuentas-por-pagar.detalle.pagar', ['detalle' => 'DETALLE_ID'])).replace('DETALLE_ID', this.detalleId);
                successText = `Abono registrado por ${this.montoAbonoFmt}.`;
              }

              const res = await fetch(url, {
                method:'POST',
                headers:{
                  'X-CSRF-TOKEN': @json(csrf_token()),
                  'X-Requested-With': 'XMLHttpRequest',
                  'Accept': 'application/json'
                },
                body: data
              });

              // ⚠️ Validación (422)
              if (res.status === 422) {
                const json = await res.json();
                const labels = { monto:'Monto', fecha_pago:'Fecha de pago', caja_id:'Caja origen', comentario:'Comentario', estado:'Estado' };
                const items = [];
                for (const [field, arr] of Object.entries(json.errors || {})) {
                  items.push(`<li><strong>${labels[field] || field}:</strong> ${arr.join(', ')}</li>`);
                }
                const html = `<ul style="text-align:left;margin:0 6px 0 18px">${items.join('')}</ul>`;
                await this.notify({icon:'warning', title:'Revisa los campos', html, timer:3500});
                return;
              }

              if (!res.ok) {
                const txt = await res.text();
                throw new Error(txt || 'Error desconocido');
              }

              // ✅ ÉXITO
              this.modalOpen = false;
              await this.notify({icon:'success', title:'¡Pago registrado!', text: successText});

              // Refresca fragmento y vista
              try { await this.reloadVencidos(this.cuentaId); } catch(_) {}
              window.location.reload();

            } catch(e){
              await this.notify({icon:'error', title:'No se pudo guardar', text: (e && e.message) ? e.message : 'Intenta nuevamente.'});
            }
          },

          async toggleVencidos(cuentaId){
            const cont = document.getElementById(`vencidos-${cuentaId}`);
            if (!cont) return;
            if (cont.dataset.loaded === '1') { cont.classList.toggle('hidden'); return; }
            await this.reloadVencidos(cuentaId);
            cont.classList.remove('hidden');
          },

          async reloadVencidos(cuentaId){
            const cont = document.getElementById(`vencidos-${cuentaId}`);
            if (!cont) return;
            cont.innerHTML = '<div class="text-sm text-gray-500 px-4 py-2">Cargando…</div>';
            try {
              const url = @json(route('cuentas-por-pagar.vencidos', ['cuenta'=>'CID'])).replace('CID', cuentaId);
              const html = await (await fetch(url, { headers:{ 'X-Requested-With':'XMLHttpRequest' } })).text();
              cont.innerHTML = html;
              cont.dataset.loaded = '1';
              if (window.Alpine && Alpine.initTree) Alpine.initTree(cont);
            } catch(e){
              cont.innerHTML = '<div class="text-sm text-red-600 px-4 py-2">Error al cargar vencidos.</div>';
            }
          },
        });
        window.__CPP_STORE_READY = true;
      }
      document.addEventListener('alpine:init', createStore);
      if (window.Alpine) createStore();
    })();

    function cppIndexPage() {
      return {
        activeTab: new URLSearchParams(window.location.search).get('tab') === 'abonos' ? 'abonos' : 'registro',

        search: @json(request('search','')),
        estado: @json(request('estado','')),
        sucursal: @json(request('sucursal','')),
        loading: false,
        container: null,

        init() {
          this.container = document.getElementById('cuentas-results');
          this.container?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a?.href || !/([?&])page=/.test(a.href)) return;
            e.preventDefault();
            const url = new URL(a.href); url.searchParams.set('ajax','1');
            this.fetchTo(url.toString());
          });

          window.__abonosApply = async (payload) => {
            const target = document.getElementById('abonos-content');
            if (!target) return;

            const base = @json(route('cuentas-por-pagar.abonos.index'));
            const p = new URLSearchParams();
            if (payload.q)           p.set('q', payload.q);
            if (payload.estado)      p.set('estado', payload.estado);
            if (payload.sucursal_id) p.set('sucursal_id', payload.sucursal_id);
            if (payload.desde)       p.set('desde', payload.desde);
            if (payload.hasta)       p.set('hasta', payload.hasta);
            p.set('ajax','1');

            await this.refreshAbonos(false, `${base}?${p.toString()}`);
          };

          if (this.activeTab === 'abonos') this.refreshAbonos(true);
        },

        handleOpenPay(payload){
          const store = window.Alpine?.store('cpp');
          if (!store) return;
          store.openPayModal(payload || {});
        },

        openTab(tab){
          this.activeTab = tab;
          const u = new URL(window.location.href);
          if (tab === 'abonos') { u.searchParams.set('tab','abonos'); this.refreshAbonos(true); }
          else { u.searchParams.delete('tab'); }
          history.replaceState({}, '', u);
        },

        buildUrl() {
          const base = @json(route('cuentas-por-pagar.index'));
          const params = new URLSearchParams({
            search: this.search ?? '',
            estado: this.estado ?? '',
            sucursal: this.sucursal ?? '',
            ajax: '1',
          });
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
              const frag = doc.querySelector('#cuentas-results');
              if (frag) htmlToInject = frag.innerHTML;
            } catch (_) {}
            if (this.container) {
              this.container.innerHTML = htmlToInject;
              if (window.Alpine?.initTree) Alpine.initTree(this.container);
            }
            const u = new URL(url, window.location.origin);
            u.searchParams.delete('ajax');
            history.replaceState({}, '', u);
          } catch (e) { /* noop */ }
          finally { this.loading = false; }
        },

        attachAbonosEvents(target){
          target?.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (a?.href && /([?&])page=/.test(a.href)) {
              e.preventDefault();
              this.refreshAbonos(false, a.href);
              return;
            }
          });
        },

        async refreshAbonos(first=false, urlOverride=null){
          const target = document.getElementById('abonos-content');
          if (!target) return;
          target.innerHTML = '<div class="text-sm text-gray-500">Cargando abonos…</div>';

          try{
            const url = urlOverride ?? (() => {
              const base = @json(route('cuentas-por-pagar.abonos.index'));
              const p = new URLSearchParams({ ajax:'1' });
              return `${base}?${p.toString()}`;
            })();

            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const text = await res.text();

            let htmlToInject = text;
            try{
              const doc = new DOMParser().parseFromString(text, 'text/html');
              const frag = doc.querySelector('#abonos-frag');
              if (frag) htmlToInject = frag.outerHTML;
            }catch(_){}

            target.innerHTML = htmlToInject;
            if (window.Alpine?.initTree) Alpine.initTree(target);
            this.attachAbonosEvents(target);

            const u = new URL(window.location.href);
            u.searchParams.set('tab','abonos');
            history.replaceState({}, '', u);

          }catch(e){
            target.innerHTML = '<div class="text-sm text-red-600">No se pudo cargar Abonos.</div>';
          }
        },
      }
    }
  </script>

  {{-- MODAL GLOBAL DE PAGO (teleport centrado) --}}
  <template x-teleport="body">
    <div
      x-cloak
      x-show="$store.cpp.modalOpen"
      x-transition.opacity
      class="fixed inset-0 z-[9999] flex items-start justify-center"
      @keydown.escape.window="$store.cpp.closeModal()"
    >
      <div class="absolute inset-0 bg-black/40" @click="$store.cpp.closeModal()"></div>

      <div
        class="relative mt-12 w-full max-w-md rounded-xl bg-white p-5 shadow-xl dark:bg-gray-900"
        x-trap.noscroll="$store.cpp.modalOpen"
        x-transition.scale.origin-top
        @click.stop
      >
        <div class="mb-3 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Registrar pago</h3>
          <button class="text-gray-500" @click="$store.cpp.closeModal()">✕</button>
        </div>

        <form x-ref="payForm" @submit.prevent="$store.cpp.submitPago($refs.payForm)">
          {{-- Estado fijo (no editable) --}}
          <input type="hidden" name="estado" value="pagado">

          <div class="space-y-3">
            <div>
              <label class="block text-sm text-gray-600 dark:text-gray-300">Monto</label>
              <input type="number" step="0.01" name="monto" required
                     :value="$store.cpp.montoAbono"
                     class="mt-1 w-full rounded-md border px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"/>
            </div>

            <div>
              <label class="block text-sm text-gray-600 dark:text-gray-300">Fecha de pago</label>
              <input type="date" name="fecha_pago" required
                     class="mt-1 w-full rounded-md border px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                     value="{{ now()->toDateString() }}">
            </div>

            <div>
              <label class="block text-sm text-gray-600 dark:text-gray-300">Caja origen</label>
              <select name="caja_id" required
                      class="mt-1 w-full rounded-md border px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                <option value="">-- Selecciona caja --</option>
                @foreach(($cajas ?? []) as $c)
                  <option value="{{ $c->id_caja }}">{{ $c->nombre }}</option>
                @endforeach
              </select>
            </div>

            <div>
              <label class="block text-sm text-gray-600 dark:text-gray-300">Comentario</label>
              <textarea name="comentario" rows="3"
                        class="mt-1 w-full rounded-md border px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"></textarea>
            </div>
          </div>

          <div class="mt-5 flex justify-end gap-2">
            <button type="button" @click="$store.cpp.closeModal()"
                    class="rounded-lg bg-gray-200 px-4 py-2 dark:bg-gray-700 dark:text-gray-100">Cancelar</button>
            <button type="submit"
                    class="rounded-lg bg-green-600 px-4 py-2 text-white hover:bg-green-700">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </template>
</x-app-layout>
