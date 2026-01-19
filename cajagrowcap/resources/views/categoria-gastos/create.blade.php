{{-- resources/views/categoria_gastos/create.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Crear Categoría de Gasto') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <x-validation-errors class="mb-4"/>
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
      <form action="{{ route('categoria-gastos.store') }}" method="POST">
        @csrf
        <div class="mb-4">
          <x-label for="nombre" value="Nombre" />
          <x-input id="nombre" name="nombre" type="text" :value="old('nombre')" required autofocus />
        </div>
        <div class="flex justify-end">
          <a href="{{ route('categoria-gastos.index') }}"
             class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded">Cancelar</a>
          <x-button type="submit" class="ml-2 bg-purple-600 hover:bg-purple-700">
            Guardar
          </x-button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
