<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Nuevo Proveedor') }}
      </h2>
      <a href="{{ request('back', route('proveedores.index')) }}"
         class="px-3 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md">
        ← {{ __('Volver') }}
      </a>
    </div>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <form action="{{ route('proveedores.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Mantener URL de retorno al guardar o si hay validación --}}
        <input type="hidden" name="back" value="{{ old('back', request('back')) }}">

        @include('proveedores._form')

        <div class="text-right">
          <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">
            {{ __('Guardar') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
