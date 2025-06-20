{{-- resources/views/sucursales/show.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Detalles de la Sucursal') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    {{-- Tarjeta --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
      {{-- Nombre --}}
      <div>
        <h3 class="text-gray-500 dark:text-gray-400 uppercase text-xs font-medium">{{ __('Nombre') }}</h3>
        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $sucursal->nombre }}</p>
      </div>

      {{-- Dirección --}}
      <div>
        <h3 class="text-gray-500 dark:text-gray-400 uppercase text-xs font-medium">{{ __('Dirección') }}</h3>
        <p class="mt-1 text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $sucursal->direccion }}</p>
      </div>

      {{-- Teléfono --}}
      <div>
        <h3 class="text-gray-500 dark:text-gray-400 uppercase text-xs font-medium">{{ __('Teléfono') }}</h3>
        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $sucursal->telefono }}</p>
      </div>

      {{-- Gerente --}}
      <div>
        <h3 class="text-gray-500 dark:text-gray-400 uppercase text-xs font-medium">{{ __('Gerente') }}</h3>
        <p class="mt-1 text-gray-900 dark:text-gray-100">
          {{ optional($sucursal->gerente)->name ?? __('— Sin asignar —') }}
        </p>
      </div>

      {{-- Política crediticia --}}
      @if($sucursal->politica_crediticia)
      <div>
        <h3 class="text-gray-500 dark:text-gray-400 uppercase text-xs font-medium">{{ __('Política crediticia') }}</h3>
        <p class="mt-1 text-gray-900 dark:text-gray-100 whitespace-pre-line">
          {{ $sucursal->politica_crediticia }}
        </p>
      </div>
      @endif

      {{-- Activa --}}
      <div>
        <h3 class="text-gray-500 dark:text-gray-400 uppercase text-xs font-medium">{{ __('Activa') }}</h3>
        @if($sucursal->acceso_activo)
          <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">
            Sí
          </span>
        @else
          <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800 dark:bg-red-900 dark:text-red-200">
            No
          </span>
        @endif
      </div>

      {{-- Creador y fechas --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <h3 class="text-gray-500 dark:text-gray-400 uppercase text-xs font-medium">{{ __('Creada por') }}</h3>
          <p class="mt-1 text-gray-900 dark:text-gray-100">
            {{ optional($sucursal->creador)->name ?? '-' }}
          </p>
        </div>
        <div>
          <h3 class="text-gray-500 dark:text-gray-400 uppercase text-xs font-medium">{{ __('Fecha de creación') }}</h3>
          <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $sucursal->created_at->format('d/m/Y H:i') }}</p>
        </div>
      </div>
    </div>

    {{-- Botones --}}
    <div class="mt-6 flex space-x-3">
      <a href="{{ route('sucursales.index') }}"
         class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 
                text-gray-800 dark:text-gray-200 font-medium rounded-md shadow-sm transition">
        {{ __('← Volver') }}
      </a>

      <a href="{{ route('sucursales.edit', $sucursal) }}"
         class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 
                text-white font-medium rounded-md shadow-sm transition">
        {{ __('Editar') }}
      </a>
    </div>
  </div>
</x-app-layout>
