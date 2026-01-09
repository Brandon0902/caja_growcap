<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Ver Inversion') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 space-y-4">

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Nombre</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $inversion->nombre ?? '—' }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Periodo</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $inversion->periodo ?? '—' }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Monto Minimo</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $inversion->monto_minimo ?? '—' }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Monto Maximo</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $inversion->monto_maximo ?? '—' }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Rendimiento (%)</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $inversion->rendimiento ?? '—' }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Fecha</h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ $inversion->fecha ? \Carbon\Carbon::parse($inversion->fecha)->format('d/m/Y') : '—' }}
        </p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Estado</h3>
        <p class="text-gray-700 dark:text-gray-300">
          @if((string)$inversion->status === '1')
            Activo
          @elseif((string)$inversion->status === '2')
            Inactivo
          @else
            —
          @endif
        </p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Creado por</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ optional($inversion->usuario)->name ?? '—' }}</p>
      </div>

      <div class="flex justify-end">
        <a href="{{ route('inversiones.index') }}"
           class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded">
          {{ __('Volver') }}
        </a>
      </div>

    </div>
  </div>
</x-app-layout>
