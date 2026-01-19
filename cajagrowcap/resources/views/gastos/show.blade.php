<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Detalle de transacción entre cajas') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 space-y-6">

      {{-- Tipo de transacción --}}
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">
          {{ __('Tipo de transacción') }}
        </h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $gasto->tipo }}</p>
      </div>

      {{-- Caja origen --}}
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">
          {{ __('Caja origen') }}
        </h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ optional($gasto->cajaOrigen)->nombre ?? '—' }}
        </p>
      </div>

      {{-- Caja destino --}}
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">
          {{ __('Caja destino') }}
        </h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ optional($gasto->cajaDestino)->nombre ?? '—' }}
        </p>
      </div>

      {{-- Monto --}}
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">
          {{ __('Monto') }}
        </h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ number_format($gasto->cantidad, 2) }}
        </p>
      </div>

      {{-- Concepto --}}
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">
          {{ __('Concepto') }}
        </h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $gasto->concepto ?: '—' }}</p>
      </div>

      {{-- Comprobante (si existe) --}}
      @if($gasto->comprobante)
        <div>
          <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">
            {{ __('Comprobante') }}
          </h3>

          @php
            $isImage = preg_match('/\.(jpe?g|png)$/i', $gasto->comprobante ?? '');
          @endphp

          <div class="mt-2">
            <a href="{{ route('gastos.comprobante', $gasto) }}" target="_blank"
               class="text-blue-600 hover:underline dark:text-blue-300">
              {{ __('Abrir comprobante en una nueva pestaña') }}
            </a>
          </div>

          @if($isImage)
            <div class="mt-3">
              <img src="{{ route('gastos.comprobante', $gasto) }}"
                   alt="Comprobante"
                   class="max-h-80 rounded shadow object-contain bg-gray-50 dark:bg-gray-700 p-2" />
            </div>
          @endif
        </div>
      @endif

      {{-- Metadatos (opcional) --}}
      <div class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('Creado') }}: {{ optional($gasto->created_at)->format('Y-m-d H:i') }} ·
        {{ __('Actualizado') }}: {{ optional($gasto->updated_at)->format('Y-m-d H:i') }}
      </div>

      {{-- Volver --}}
      <div class="flex justify-end">
        <a href="{{ route('gastos.index') }}"
           class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded
                  text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-100">
          {{ __('Volver a transacciones') }}
        </a>
      </div>

    </div>
  </div>
</x-app-layout>
