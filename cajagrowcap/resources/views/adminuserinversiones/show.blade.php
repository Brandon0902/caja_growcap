<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Inversión #') }}{{ str_pad($inversion->id, 3, '0', STR_PAD_LEFT) }}
      </h2>
      <a href="{{ route('user_inversiones.index') }}"
         class="inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600
                text-white text-sm font-medium rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-gray-400">
        {{ __('← Volver') }}
      </a>
    </div>
  </x-slot>

  <div class="py-6 mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Cliente') }}</h3>
          <p class="text-lg text-gray-800 dark:text-gray-100">
            {{ optional($inversion->cliente)->nombre }} {{ optional($inversion->cliente)->apellido }}
          </p>
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ optional($inversion->cliente)->email }}</p>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Plan') }}</h3>
          <p class="text-lg text-gray-800 dark:text-gray-100">
            {{ optional($inversion->plan)->periodo }} {{ __('meses') }}
            — {{ number_format($inversion->rendimiento ?? optional($inversion->plan)->rendimiento, 2) }}%
          </p>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Monto') }}</h3>
          <p class="text-lg text-gray-800 dark:text-gray-100">
            ${{ number_format($inversion->inversion ?? 0, 2) }}
          </p>
        </div>

        {{-- ✅ FECHAS (agregamos FIN) --}}
        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Fechas') }}</h3>

          <p class="text-gray-800 dark:text-gray-100">
            {{ __('Solicitud:') }}
            {{ $inversion->fecha_solicitud ? \Carbon\Carbon::parse($inversion->fecha_solicitud)->format('Y-m-d H:i') : '—' }}
          </p>

          <p class="text-gray-800 dark:text-gray-100">
            {{ __('Inicio:') }}
            {{ $inversion->fecha_inicio ? \Carbon\Carbon::parse($inversion->fecha_inicio)->format('Y-m-d') : '—' }}
          </p>

          <p class="text-gray-800 dark:text-gray-100">
            {{ __('Fin:') }}
            {{ $inversion->fecha_fin ? \Carbon\Carbon::parse($inversion->fecha_fin)->format('Y-m-d') : '—' }}
          </p>

          @if($inversion->fecha_respuesta)
            <p class="text-gray-800 dark:text-gray-100">
              {{ __('Respuesta:') }}
              {{ \Carbon\Carbon::parse($inversion->fecha_respuesta)->format('Y-m-d H:i') }}
            </p>
          @endif
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Estatus') }}</h3>
          @php $labels = [1=>'Pendiente', 2=>'Activa', 3=>'Inactiva']; @endphp
          <p class="text-lg text-gray-800 dark:text-gray-100">
            {{ $labels[$inversion->status] ?? '—' }}
          </p>
          @if($inversion->nota)
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('Nota:') }} {{ $inversion->nota }}</p>
          @endif
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Caja asociada') }}</h3>
          <p class="text-gray-800 dark:text-gray-100">{{ optional($inversion->caja)->nombre ?? '—' }}</p>
        </div>
      </div>

      <div class="flex justify-end gap-2">
        <a href="{{ route('user_inversiones.edit', $inversion) }}"
           class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md">
          {{ __('Editar') }}
        </a>
      </div>
    </div>
  </div>
</x-app-layout>
