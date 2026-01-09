<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Ahorro #') }}{{ str_pad($ahorro->id, 3, '0', STR_PAD_LEFT) }}
      </h2>
      <a href="{{ route('user_ahorros.index') }}"
         class="inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600
                text-white text-sm font-medium rounded-md shadow-sm">
        {{ __('← Volver al listado') }}
      </a>
    </div>
  </x-slot>

  <div class="py-6 mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
      {{-- Resumen --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Cliente') }}</h3>
          <p class="text-base text-gray-800 dark:text-gray-100">
            {{ optional($ahorro->cliente)->nombre }} {{ optional($ahorro->cliente)->apellido }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ optional($ahorro->cliente)->email }}</p>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Tipo') }}</h3>
          <p class="text-base text-gray-800 dark:text-gray-100">
            {{ optional($ahorro->ahorro)->tipo_ahorro ?? ($ahorro->tipo ?? '—') }}
          </p>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Monto') }}</h3>
          <p class="text-base text-gray-800 dark:text-gray-100">${{ number_format($ahorro->monto_ahorro,2) }}</p>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Rendimiento') }}</h3>
          <p class="text-base text-gray-800 dark:text-gray-100">{{ number_format($ahorro->rendimiento,2) }}%</p>
          <p class="text-sm text-gray-700 dark:text-gray-200">
            {{ __('Generado:') }} ${{ number_format($ahorro->rendimiento_generado,2) }}
          </p>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Fechas') }}</h3>
          <p class="text-sm text-gray-800 dark:text-gray-100">
            {{ __('Inicio:') }} {{ \Carbon\Carbon::parse($ahorro->fecha_inicio)->format('Y-m-d') }}
          </p>
        </div>

        @php
          $statusLabel = $statusOptions[$ahorro->status] ?? '—';
          $badgeClass  = ((int)$ahorro->status === 1)
            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200'
            : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
        @endphp
        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Status') }}</h3>
          <p>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">{{ $statusLabel }}</span>
          </p>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Caja') }}</h3>
          <p class="text-base text-gray-800 dark:text-gray-100">
            {{ optional($ahorro->caja)->nombre ?? '—' }}
          </p>
        </div>

        @if(!empty($ahorro->nota))
          <div class="md:col-span-2">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Nota') }}</h3>
            <p class="text-base text-gray-800 dark:text-gray-100">{{ $ahorro->nota }}</p>
          </div>
        @endif
      </div>
    </div>
  </div>
</x-app-layout>
