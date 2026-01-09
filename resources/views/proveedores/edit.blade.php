<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Editar Proveedor') }} #{{ str_pad($proveedor->id_proveedor, 3, '0', STR_PAD_LEFT) }}
      </h2>
      <a href="{{ request('back', route('proveedores.index')) }}"
         class="px-3 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md">
        ← {{ __('Volver') }}
      </a>
    </div>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <form action="{{ route('proveedores.update', $proveedor->id_proveedor) }}" method="POST" class="space-y-6">
        @csrf @method('PUT')

        {{-- Mantener URL de retorno al actualizar o si hay validación --}}
        <input type="hidden" name="back" value="{{ old('back', request('back')) }}">

        @include('proveedores._form', ['proveedor' => $proveedor])

        <div class="text-right">
          <button type="submit" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md">
            {{ __('Actualizar') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
