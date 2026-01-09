{{-- resources/views/contabilidad/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
      {{ __('Contabilidad Profunda') }}
    </h2>
  </x-slot>

  <div class="px-4 py-6 space-y-6">

    {{-- KPIs superiores --}}
    @isset($kpi)
      <div class="grid gap-4 grid-cols-2 md:grid-cols-4 xl:grid-cols-6">
        <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-xl">
          <div class="text-xs text-gray-500">Movimientos</div>
          <div class="text-2xl font-bold">{{ $kpi['movimientos'] }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-xl">
          <div class="text-xs text-gray-500">Ingresos</div>
          <div class="text-2xl font-bold text-green-600">{{ number_format($kpi['ingresos'],2) }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-xl">
          <div class="text-xs text-gray-500">Egresos</div>
          <div class="text-2xl font-bold text-red-600">{{ number_format($kpi['egresos'],2) }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-xl">
          <div class="text-xs text-gray-500">Balance</div>
          <div class="text-2xl font-bold {{ $kpi['balance']>=0 ? 'text-green-600':'text-red-600' }}">
            {{ number_format($kpi['balance'],2) }}
          </div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-xl">
          <div class="text-xs text-gray-500">Cajas</div>
          <div class="text-2xl font-bold">{{ $kpi['cajas'] }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-xl">
          <div class="text-xs text-gray-500">Usuarios distintos</div>
          <div class="text-2xl font-bold">{{ $kpi['usuarios'] }}</div>
        </div>
      </div>
    @endisset

    {{-- Tarjetas: Presupuesto vs Gasto por Fuente --}}
    @isset($movs)
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach($fuentes as $f)
          @php
            $gasto = ($gastoPorFuente[$f] ?? 0);
            $pres  = $presupuestos[$f]->monto ?? 0;
            $pct   = $pres > 0 ? min(100, round($gasto * 100 / $pres)) : null;
          @endphp
          <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-xl">
            <div class="text-sm text-gray-500">{{ $f }}</div>
            <div class="mt-1 text-xl font-bold text-red-600">{{ number_format($gasto,2) }}</div>
            <div class="text-xs text-gray-500">/ {{ number_format($pres,2) }} presupuestado</div>
            @if(!is_null($pct))
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2 overflow-hidden">
                <div class="h-2 {{ $pct >= 100 ? 'bg-red-500':'bg-green-500' }}" style="width: {{ $pct }}%"></div>
              </div>
              <div class="text-xs text-gray-500 mt-1">{{ $pct }}% usado</div>
            @endif
          </div>
        @endforeach
      </div>
    @endisset

    {{-- Zona de gráficas principales --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      {{-- Barras: Ingresos vs Egresos por día --}}
      <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-xl lg:col-span-2">
        <div class="text-sm text-gray-500 mb-2">Ingresos vs Egresos (por día)</div>
        <canvas id="chartIE"></canvas>
      </div>

      {{-- Dona doble: Ingresos/Egresos por fuente --}}
      <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-xl">
        <div class="text-sm text-gray-500 mb-2">Ingresos / Egresos por fuente</div>
        <canvas id="chartFuente"></canvas>
      </div>
    </div>

    {{-- Donas dobles extra: Caja / Categoría / Subcategoría --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-xl">
        <div class="text-sm text-gray-500 mb-2">Ingresos / Egresos por caja</div>
        <canvas id="chartCaja"></canvas>
      </div>
      <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-xl">
        <div class="text-sm text-gray-500 mb-2">Ingresos / Egresos por categoría</div>
        <canvas id="chartCat"></canvas>
      </div>
      <div class="p-4 bg-white dark:bg-gray-800 shadow rounded-xl">
        <div class="text-sm text-gray-500 mb-2">Ingresos / Egresos por subcategoría</div>
        <canvas id="chartSub"></canvas>
      </div>
    </div>

    {{-- Barra de filtros sticky --}}
    <div class="sticky top-16 z-10">
      <div class="p-4 bg-white/95 dark:bg-gray-800/95 backdrop-blur shadow rounded-xl">
        <form method="GET" action="{{ route('contabilidad.index') }}">
          <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3 items-end">
            {{-- Mes/Año --}}
            <div>
              <label class="block text-xs text-gray-600 dark:text-gray-300">Mes</label>
              <select name="mes" class="w-full border rounded px-2 py-1">
                <option value="">—</option>
                @foreach($meses as $n => $nombre)
                  <option value="{{ $n }}" @selected(($mes ?? '') == $n)>{{ $nombre }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-600 dark:text-gray-300">Año</label>
              <select name="año" class="w-full border rounded px-2 py-1">
                <option value="">—</option>
                @foreach($años as $y)
                  <option value="{{ $y }}" @selected(($año ?? '') == $y)>{{ $y }}</option>
                @endforeach
              </select>
            </div>

            {{-- Rango fechas --}}
            <div>
              <label class="block text-xs text-gray-600 dark:text-gray-300">Desde</label>
              <input type="date" name="desde" value="{{ $desde ?? '' }}" class="w-full border rounded px-2 py-1">
            </div>
            <div>
              <label class="block text-xs text-gray-600 dark:text-gray-300">Hasta</label>
              <input type="date" name="hasta" value="{{ $hasta ?? '' }}" class="w-full border rounded px-2 py-1">
            </div>

            {{-- Tipo / Fuente --}}
            <div>
              <label class="block text-xs text-gray-600 dark:text-gray-300">Tipo</label>
              <select name="tipo" class="w-full border rounded px-2 py-1">
                <option value="">Ambos</option>
                <option value="ingreso" @selected(($tipo ?? '')==='ingreso')>Ingresos</option>
                <option value="egreso"  @selected(($tipo ?? '')==='egreso')>Egresos</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-600 dark:text-gray-300">Fuente</label>
              <select name="fuente" class="w-full border rounded px-2 py-1">
                <option value="">Todas</option>
                @foreach($fuentes as $f)
                  <option value="{{ $f }}" @selected(($fuente ?? '')===$f)>{{ $f }}</option>
                @endforeach
              </select>
            </div>

            {{-- Entidades --}}
            <div>
              <label class="block text-xs text-gray-600 dark:text-gray-300">Cliente</label>
              <select name="cliente_id" class="w-full border rounded px-2 py-1">
                <option value="">—</option>
                @foreach($clientes as $id => $n)
                  <option value="{{ $id }}" @selected(($cliente_id ?? '')==$id)>{{ $n }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-600 dark:text-gray-300">Sucursal</label>
              <select name="sucursal_id" class="w-full border rounded px-2 py-1">
                <option value="">—</option>
                @foreach($sucursales as $id => $n)
                  <option value="{{ $id }}" @selected(($sucursal_id ?? '')==$id)>{{ $n }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-600 dark:text-gray-300">Caja</label>
              <select name="caja_id" class="w-full border rounded px-2 py-1">
                <option value="">—</option>
                @foreach($cajas as $id => $n)
                  <option value="{{ $id }}" @selected(($caja_id ?? '')==$id)>{{ $n }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-600 dark:text-gray-300">Usuario</label>
              <select name="usuario_id" class="w-full border rounded px-2 py-1">
                <option value="">—</option>
                @foreach($usuarios as $id => $n)
                  <option value="{{ $id }}" @selected(($usuario_id ?? '')==$id)>{{ $n }}</option>
                @endforeach
              </select>
            </div>

            {{-- IDs categoría/subcategoría --}}
            <div>
              <label class="block text-xs text-gray-600 dark:text-gray-300">ID Categoría</label>
              <input type="number" name="categoria_id" value="{{ $categoria_id ?? '' }}" class="w-full border rounded px-2 py-1">
            </div>
            <div>
              <label class="block text-xs text-gray-600 dark:text-gray-300">ID Subcategoría</label>
              <input type="number" name="subcategoria_id" value="{{ $subcategoria_id ?? '' }}" class="w-full border rounded px-2 py-1">
            </div>

            <div class="md:col-span-2 xl:col-span-1">
              <button class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Aplicar
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    {{-- Tabla con scroll interno --}}
    @isset($movs)
      <div class="bg-white dark:bg-gray-800 shadow rounded-xl">
        <div class="max-h-[60vh] overflow-y-auto rounded-b-xl">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium">Fecha</th>
                <th class="px-4 py-2 text-left text-xs font-medium">Fuente</th>
                <th class="px-4 py-2 text-left text-xs font-medium">Cliente</th>
                <th class="px-4 py-2 text-left text-xs font-medium">Sucursal</th>
                <th class="px-4 py-2 text-left text-xs font-medium">Caja</th>
                <th class="px-4 py-2 text-left text-xs font-medium">Usuario</th>
                <th class="px-4 py-2 text-left text-xs font-medium">Categoría</th>
                <th class="px-4 py-2 text-left text-xs font-medium">Subcategoría</th>
                <th class="px-4 py-2 text-left text-xs font-medium">Descripción</th>
                <th class="px-4 py-2 text-right text-xs font-medium">Monto</th>
                <th class="px-4 py-2 text-left text-xs font-medium">Tipo</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              @foreach($movs as $m)
                @php
                  $catNombre = $m->tipo === 'ingreso' ? optional($m->categoriaIngreso)->nombre : optional($m->categoriaGasto)->nombre;
                  $subNombre = $m->tipo === 'ingreso' ? optional($m->subcategoriaIngreso)->nombre : optional($m->subcategoriaGasto)->nombre;
                @endphp
                <tr class="{{ $m->tipo=='egreso' ? 'bg-red-50 dark:bg-red-900/30' : 'bg-green-50 dark:bg-green-900/30' }}">
                  <td class="px-4 py-2 whitespace-nowrap">{{ $m->fecha?->format('d-m-Y') }}</td>
                  <td class="px-4 py-2 whitespace-nowrap">{{ $m->fuente }}</td>
                  <td class="px-4 py-2 whitespace-nowrap">{{ $m->cliente->nombre ?? '—' }}</td>
                  <td class="px-4 py-2 whitespace-nowrap">{{ $m->sucursal->nombre ?? '—' }}</td>
                  <td class="px-4 py-2 whitespace-nowrap">{{ $m->caja->nombre ?? '—' }}</td>
                  <td class="px-4 py-2 whitespace-nowrap">{{ $m->user->name ?? '—' }}</td>
                  <td class="px-4 py-2 whitespace-nowrap">{{ $catNombre ?? '—' }}</td>
                  <td class="px-4 py-2 whitespace-nowrap">{{ $subNombre ?? '—' }}</td>
                  <td class="px-4 py-2">{{ $m->descripcion }}</td>
                  <td class="px-4 py-2 text-right whitespace-nowrap">{{ number_format($m->monto,2) }}</td>
                  <td class="px-4 py-2 capitalize whitespace-nowrap">{{ $m->tipo }}</td>
                </tr>
              @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-700">
              <tr class="font-semibold">
                <td colspan="9" class="px-4 py-2 text-right">Balance neto:</td>
                <td class="px-4 py-2 text-right">{{ number_format(($kpi['balance'] ?? 0), 2) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    @endisset

  </div>

  {{-- Chart.js CDN --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  // ===== Datos desde PHP =====
  const labelsDias      = @json($labelsDias ?? []);
  const ingresosDia     = @json($ingresosDia ?? []);
  const egresosDia      = @json($egresosDia ?? []);

  const labelsFuente    = @json($labelsFuente ?? []);
  const datosFuenteIng  = @json($datosFuenteIng ?? []);
  const datosFuenteEgr  = @json($datosFuenteEgr ?? []);

  const labelsCaja      = @json($labelsCaja ?? []);
  const datosCajaIng    = @json($datosCajaIng ?? []);
  const datosCajaEgr    = @json($datosCajaEgr ?? []);

  const labelsCat       = @json($labelsCat ?? []);
  const datosCatIng     = @json($datosCatIng ?? []);
  const datosCatEgr     = @json($datosCatEgr ?? []);

  const labelsSub       = @json($labelsSub ?? []);
  const datosSubIng     = @json($datosSubIng ?? []);
  const datosSubEgr     = @json($datosSubEgr ?? []);

  // ===== Barra Ingresos vs Egresos por día =====
  const ctxIE = document.getElementById('chartIE');
  if (ctxIE) {
    new Chart(ctxIE, {
      type: 'bar',
      data: {
        labels: labelsDias,
        datasets: [
          { label: 'Ingresos', data: ingresosDia, backgroundColor: 'rgba(34,197,94,0.7)' },
          { label: 'Egresos',  data: egresosDia,  backgroundColor: 'rgba(239,68,68,0.7)' },
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { position: 'bottom' } }
      }
    });
    ctxIE.parentElement.style.height = '320px';
  }

  // ===== Paleta de colores distinta por etiqueta =====
  function makePalette(n, alpha = 0.85, light = 55, offset = 0) {
    const colors = [];
    for (let i = 0; i < n; i++) {
      const hue = (i * 360 / Math.max(1, n) + offset) % 360;
      colors.push(`hsl(${hue} 70% ${light}% / ${alpha})`);
    }
    return colors;
  }

  // ===== Helper: dona de 2 anillos (Ingresos / Egresos) con paletas distintas =====
  function renderDualDonut(id, labels, dataIng, dataEgr) {
    const el = document.getElementById(id);
    if (!el) return;

    // Colores: cada etiqueta un color; mismo tono para egresos pero más oscuro/otra opacidad
    const colorsIng = makePalette(labels.length, 0.85, 58,   0);  // más brillante
    const colorsEgr = makePalette(labels.length, 0.65, 42,  12);  // mismo círculo cromático pero desplazado/oscuro

    new Chart(el, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [
          // Primer dataset (leyenda tomará estos colores)
          { label: 'Ingresos', data: dataIng, backgroundColor: colorsIng, borderWidth: 1, borderColor: '#fff' },
          // Segundo anillo
          { label: 'Egresos',  data: dataEgr, backgroundColor: colorsEgr, borderWidth: 1, borderColor: '#fff' }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '55%',      // agujero central
        radius: '95%',      // tamaño total
        plugins: { legend: { position: 'bottom' } },
        animation: { animateRotate: true, animateScale: true }
      }
    });

    el.parentElement.style.height = '300px';
  }

  // ===== Donas dobles =====
  renderDualDonut('chartFuente', labelsFuente, datosFuenteIng, datosFuenteEgr);
  renderDualDonut('chartCaja',   labelsCaja,   datosCajaIng,   datosCajaEgr);
  renderDualDonut('chartCat',    labelsCat,    datosCatIng,    datosCatEgr);
  renderDualDonut('chartSub',    labelsSub,    datosSubIng,    datosSubEgr);
</script>
</x-app-layout>
