<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Ver Tipo de Préstamo') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 space-y-4">

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Periodo</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $prestamo->periodo }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Semanas</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $prestamo->semanas }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Interés (%)</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $prestamo->interes }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Monto Mínimo</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $prestamo->monto_minimo }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Monto Máximo</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $prestamo->monto_maximo }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Antigüedad (meses)</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $prestamo->antiguedad }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Estado</h3>
        <p class="text-gray-700 dark:text-gray-300">
          @switch($prestamo->status)
            @case('1') Activo @break
            @case('2') Pendiente @break
            @case('3') En revisión @break
            @case('4') Cancelado @break
          @endswitch
        </p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Creado por</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ optional($prestamo->usuario)->name }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Fecha de creación</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $prestamo->created_at->format('Y-m-d H:i') }}</p>
      </div>

      <div class="flex justify-end">
        <a href="{{ route('prestamos.index') }}"
           class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded">
          {{ __('Volver') }}
        </a>
      </div>
    </div>
  </div>
</x-app-layout>
