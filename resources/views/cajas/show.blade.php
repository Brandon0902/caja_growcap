{{-- resources/views/cajas/show.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Ver Caja') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 space-y-4">
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Nombre</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $caja->nombre }}</p>
      </div>
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Sucursal</h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ optional($caja->sucursal)->nombre ?? '-' }}
        </p>
      </div>
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Responsable</h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ optional($caja->responsable)->name ?? '-' }}
        </p>
      </div>
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Fecha de apertura</h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ $caja->fecha_apertura->format('Y-m-d H:i') }}
        </p>
      </div>
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Saldo inicial</h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ number_format($caja->saldo_inicial, 2) }}
        </p>
      </div>
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Estado</h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ ucfirst($caja->estado) }}
        </p>
      </div>
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Acceso activo</h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ $caja->acceso_activo ? 'Sí' : 'No' }}
        </p>
      </div>
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Creado por</h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ optional($caja->creador)->name ?? '-' }}
        </p>
      </div>
      <div class="flex justify-end">
        <a href="{{ route('cajas.index') }}"
           class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded">
          {{ __('Volver') }}
        </a>
      </div>
    </div>
  </div>
</x-app-layout>
