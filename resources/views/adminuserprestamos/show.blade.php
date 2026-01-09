<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Préstamo #') }}{{ str_pad($prestamo->id, 3, '0', STR_PAD_LEFT) }}
      </h2>
      <div class="flex space-x-2">
        <a href="{{ route('user_prestamos.index') }}"
           class="inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-md">
          {{ __('← Volver') }}
        </a>
        <a href="{{ route('user_prestamos.edit', $prestamo) }}"
           class="inline-flex items-center px-3 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded-md">
          {{ __('Editar') }}
        </a>
      </div>
    </div>
  </x-slot>

  <div class="py-6 mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
      <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-3">{{ __('Resumen') }}</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700 dark:text-gray-200">
        <div><span class="font-medium">{{ __('Cliente:') }}</span>
          {{ optional($prestamo->cliente)->nombre }} {{ optional($prestamo->cliente)->apellido }}
          <div class="text-xs text-gray-500">{{ optional($prestamo->cliente)->email }}</div>
        </div>
        <div><span class="font-medium">{{ __('Monto:') }}</span> ${{ number_format($prestamo->cantidad,2) }}</div>
        <div><span class="font-medium">{{ __('Tipo / Semanas:') }}</span> {{ $prestamo->tipo_prestamo }} / {{ $prestamo->semanas }}</div>
        <div><span class="font-medium">{{ __('Interés:') }}</span> {{ number_format($prestamo->interes,2) }}% ( ${{ number_format($prestamo->interes_generado,2) }} )</div>
        <div><span class="font-medium">{{ __('Inicio:') }}</span> {{ \Carbon\Carbon::parse($prestamo->fecha_inicio)->format('Y-m-d') }}</div>
        <div><span class="font-medium">{{ __('Status:') }}</span> {{ $statusOptions[$prestamo->status] ?? '—' }}</div>
        <div><span class="font-medium">{{ __('Caja:') }}</span> {{ optional($prestamo->caja)->nombre ?? '—' }}</div>
        <div><span class="font-medium">{{ __('Mora acum.:') }}</span> ${{ number_format($prestamo->mora_acumulada,2) }}</div>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
      <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-3">{{ __('Plan de pagos') }}</h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-gray-100 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">#</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Fecha Vto.</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Pagado</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Saldo Rest.</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Estado</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($prestamo->abonos as $ab)
              <tr>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $ab->num_pago }}</td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $ab->fecha_vencimiento }}</td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">${{ number_format($ab->cantidad,2) }}</td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">${{ number_format($ab->saldo_restante,2) }}</td>
                <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                  {{ [0=>'Pendiente',1=>'Pagado'][$ab->status] ?? '—' }}
                </td>
              </tr>
            @endforeach
            @if($prestamo->abonos->isEmpty())
              <tr>
                <td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                  {{ __('Sin abonos generados.') }}
                </td>
              </tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>
</x-app-layout>
