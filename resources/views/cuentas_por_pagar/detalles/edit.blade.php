{{-- resources/views/cuentas_por_pagar/detalles/edit.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white">
      {{ __('Editar Pago #') . $detalle->numero_pago . __(' de Cuenta #') . $detalle->cuenta->id_cuentas_por_pagar }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
      <form action="{{ route('detalles.update', $detalle) }}"
            method="POST">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 gap-6">

          {{-- Repite aquí los mismos campos que en create, pero con old(..., $detalle->campo) --}}
          <div>
            <label for="numero_pago" class="block text-sm font-medium">{{ __('# Pago') }}</label>
            <input type="number" name="numero_pago" id="numero_pago"
                   value="{{ old('numero_pago', $detalle->numero_pago) }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('numero_pago')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Fecha --}}
          <div>
            <label for="fecha_pago" class="block text-sm font-medium">{{ __('Fecha de Pago') }}</label>
            <input type="date" name="fecha_pago" id="fecha_pago"
                   value="{{ old('fecha_pago', $detalle->fecha_pago) }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('fecha_pago')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- (…y así sucesivamente para cada campo…) --}}

          <div class="pt-4">
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
              {{ __('Actualizar Pago') }}
            </button>
            <a href="{{ route('cuentas-por-pagar.show', $detalle->cuenta) }}"
               class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
              {{ __('Cancelar') }}
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
