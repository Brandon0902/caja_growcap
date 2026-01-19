<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
      {{ __('Dashboard') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Cabecera Ingresos del periodo --}}
    <div class="grid grid-cols-1">
      <div class="rounded-xl bg-white dark:bg-gray-800 shadow p-5 flex items-center justify-between">
        <div class="text-sm uppercase tracking-wide text-gray-500 dark:text-gray-400">
          Ingresos ({{ $start->isoFormat('DD/MM') }}—{{ $end->isoFormat('DD/MM') }})
        </div>
        <div class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">
          ${{ number_format($ingresosPeriodo, 2, '.', ',') }}
        </div>
      </div>
    </div>

    {{-- Tarjetas principales (clicables) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">

      {{-- Caja neta --}}
      <a href="{{ route('cajas.index') }}"
         class="block rounded-xl bg-white dark:bg-gray-800 shadow p-4 border-l-4 border-green-500 cursor-pointer
                transition hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        <div class="flex items-start justify-between">
          <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Caja neta</h4>
          <div class="px-2 py-2 rounded-lg bg-gradient-to-br from-green-500 to-green-600 text-white">
            <i data-lucide="banknote" class="w-5 h-5"></i>
          </div>
        </div>
        <div class="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100">
          ${{ number_format($cajaNeta, 2, '.', ',') }}
        </div>
      </a>

      {{-- Depósitos nuevos (status 0) --}}
      <a href="{{ route('depositos.index') }}"
         class="block rounded-xl bg-white dark:bg-gray-800 shadow p-4 border-l-4 border-amber-500 cursor-pointer
                transition hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        <div class="flex items-start justify-between">
          <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Depósitos nuevos</h4>
          <div class="px-2 py-2 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 text-white">
            <i data-lucide="piggy-bank" class="w-5 h-5"></i>
          </div>
        </div>
        <div class="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100">
          {{ number_format($depositosNuevos) }}
        </div>
      </a>

      {{-- Solicitudes de préstamos (pendiente/revisión) --}}
      <a href="{{ route('user_prestamos.index') }}"
         class="block rounded-xl bg-white dark:bg-gray-800 shadow p-4 border-l-4 border-slate-500 cursor-pointer
                transition hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        <div class="flex items-start justify-between">
          <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Solicitudes de préstamos</h4>
          <div class="px-2 py-2 rounded-lg bg-gradient-to-br from-slate-500 to-slate-600 text-white">
            <i data-lucide="file-text" class="w-5 h-5"></i>
          </div>
        </div>
        <div class="mt-3 space-y-1 text-sm text-gray-800 dark:text-gray-100">
          <div>Pendiente <span class="font-semibold">{{ number_format($prestPend) }}</span></div>
          <div>En revisión <span class="font-semibold">{{ number_format($prestRev) }}</span></div>
        </div>
      </a>

      {{-- Peticiones de retiros (pendientes) --}}
      @php $retTotalCount = $retInvPend + $retAhPend; @endphp
      <a href="{{ route('retiros.index') }}"
         class="block rounded-xl bg-white dark:bg-gray-800 shadow p-4 border-l-4 border-orange-500 cursor-pointer
                transition hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        <div class="flex items-start justify-between">
          <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Peticiones de retiros</h4>
          <div class="px-2 py-2 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 text-white">
            <i data-lucide="arrow-up-circle" class="w-5 h-5"></i>
          </div>
        </div>
        <div class="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100">
          {{ number_format($retTotalCount) }}
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          Inv: {{ $retInvPend }} · Ahorro: {{ $retAhPend }}
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
          Total pendiente: ${{ number_format($retTotalPend, 2, '.', ',') }}
        </div>
      </a>

      {{-- Soporte: líneas independientes clicables --}}
      <div class="rounded-xl bg-white dark:bg-gray-800 shadow p-4 border-l-4 border-slate-400">
        <div class="flex items-start justify-between mb-2">
          <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Soporte</h4>
          <div class="px-2 py-2 rounded-lg bg-gradient-to-br from-slate-400 to-slate-500 text-white">
            <i data-lucide="life-buoy" class="w-5 h-5"></i>
          </div>
        </div>
        <div class="mt-1 text-sm text-gray-800 dark:text-gray-100 space-y-2">
          <a href="{{ route('mensajes.index') }}"
             class="group flex items-center justify-between rounded-md px-2 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition focus:outline-none focus:ring-2 focus:ring-purple-500">
            <span>Mensajes</span>
            <span class="font-semibold group-hover:underline">{{ number_format($soporteMensajes) }}</span>
          </a>
          <a href="{{ route('tickets.index') }}"
             class="group flex items-center justify-between rounded-md px-2 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition focus:outline-none focus:ring-2 focus:ring-purple-500">
            <span>Tickets</span>
            <span class="font-semibold group-hover:underline">{{ number_format($soporteTickets) }}</span>
          </a>
        </div>
      </div>

      {{-- Cuentas por pagar (vencidos) --}}
      <a href="{{ route('cuentas-por-pagar.abonos.index') }}"
         class="block rounded-xl bg-white dark:bg-gray-800 shadow p-4 border-l-4 border-blue-500 cursor-pointer
                transition hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        <div class="flex items-start justify-between">
          <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">
            Cuentas por pagar <span class="text-xs text-red-500">(vencidos)</span>
          </h4>
          <div class="px-2 py-2 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 text-white">
            <i data-lucide="receipt" class="w-5 h-5"></i>
          </div>
        </div>
        <div class="mt-3 space-y-1 text-sm text-gray-800 dark:text-gray-100">
          <div>Abonos: <span class="font-semibold">{{ number_format($cxpAbonosVencidos) }}</span></div>
          <div>Saldo: <span class="font-semibold">${{ number_format($cxpSaldoVencido, 2, '.', ',') }}</span></div>
        </div>
      </a>
    </div>

    {{-- Abonos préstamos (SOLO vencidos) --}}
    <div class="grid grid-cols-1">
      <a href="{{ route('adminuserabonos.abonos.general') }}"
         class="block rounded-xl bg-white dark:bg-gray-800 shadow p-4 cursor-pointer
                transition hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Abonos préstamos</h4>
        <div class="mt-2 text-gray-900 dark:text-gray-100">
          <div class="text-sm"><span class="font-semibold">Vencidos:</span> {{ number_format($abonosVencidosCount) }}</div>
          <div class="text-sm"><span class="font-semibold">Saldo:</span> ${{ number_format($abonosVencidosSaldo, 2, '.', ',') }}</div>
        </div>
      </a>
    </div>

    {{-- ======= Gráficas (todas como antes) ======= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow">
        <h3 class="font-semibold mb-4">Distribución mensual — Depósitos</h3>
        <canvas id="chartDepositos"></canvas>
      </div>
      <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow">
        <h3 class="font-semibold mb-4">Distribución mensual — Retiros</h3>
        <canvas id="chartRetiros"></canvas>
      </div>
      <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow lg:col-span-2">
        <h3 class="font-semibold mb-4">Caja — Ingresos vs Egresos</h3>
        <canvas id="chartCaja"></canvas>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow">
        <h3 class="font-semibold mb-4">Línea mensual — Depósito / Inversión / Retiro</h3>
        <canvas id="chartLineaMensual"></canvas>
      </div>
      <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow">
        <h3 class="font-semibold mb-4">Línea anual — Depósito / Inversión / Retiro</h3>
        <canvas id="chartLineaAnual"></canvas>
      </div>
    </div>
  </div>

  @push('scripts')
    {{-- Íconos Lucide (SVG) --}}
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
      });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
      const labels = @json($labels);
      const dep    = @json($serieDepositos);
      const ret    = @json($serieRetiros);
      const ing    = @json($serieIngresos);
      const egr    = @json($serieEgresos);
      const invMensual = @json($serieInvMensual);

      new Chart(document.getElementById('chartDepositos'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Depósitos', data: dep }] },
        options: { scales: { y: { beginAtZero: true } } }
      });

      new Chart(document.getElementById('chartRetiros'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Retiros', data: ret }] },
        options: { scales: { y: { beginAtZero: true } } }
      });

      new Chart(document.getElementById('chartCaja'), {
        type: 'bar',
        data: { labels, datasets: [
          { label: 'Ingresos', data: ing },
          { label: 'Egresos', data: egr }
        ]},
        options: { scales: { y: { beginAtZero: true } } }
      });

      new Chart(document.getElementById('chartLineaMensual'), {
        type: 'line',
        data: { labels, datasets: [
          { label: 'Depósito',  data: dep,        tension: 0.3 },
          { label: 'Inversión', data: invMensual, tension: 0.3 },
          { label: 'Retiro',    data: ret,        tension: 0.3 },
        ]},
        options: { scales: { y: { beginAtZero: true } } }
      });

      const labelsYears = @json($labelsYears);
      const depYear     = @json($serieDepYear);
      const invYear     = @json($serieInvYear);
      const retYear     = @json($serieRetYear);

      new Chart(document.getElementById('chartLineaAnual'), {
        type: 'line',
        data: { labels: labelsYears, datasets: [
          { label: 'Depósito',  data: depYear, tension: 0.3 },
          { label: 'Inversión', data: invYear, tension: 0.3 },
          { label: 'Retiro',    data: retYear, tension: 0.3 },
        ]},
        options: { scales: { y: { beginAtZero: true } } }
      });
    </script>
  @endpush
</x-app-layout>
