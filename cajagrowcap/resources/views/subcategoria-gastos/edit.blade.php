{{-- resources/views/subcategoria_gastos/edit.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Editar Subcategoría') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <x-validation-errors class="mb-4"/>
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
      <form action="{{ route('subcategoria-gastos.update', $subcategoriaGasto) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-4">
          <x-label for="id_cat_gasto" value="Categoría Padre" />
          <select id="id_cat_gasto" name="id_cat_gasto"
                  class="mt-1 block w-full border rounded px-3 py-2 focus:ring-purple-500">
            <option value="">{{ __('— Seleccionar categoría —') }}</option>
            @foreach($categorias as $cat)
              <option value="{{ $cat->id_cat_gasto }}"
                {{ old('id_cat_gasto', $subcategoriaGasto->id_cat_gasto)==$cat->id_cat_gasto?'selected':'' }}>
                {{ $cat->nombre }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="mb-4">
          <x-label for="nombre" value="Nombre" />
          <x-input id="nombre" name="nombre" type="text"
                   :value="old('nombre', $subcategoriaGasto->nombre)" required />
        </div>
        <div class="flex justify-end">
          <a href="{{ route('subcategoria-gastos.index') }}"
             class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded">Cancelar</a>
          <x-button type="submit" class="ml-2 bg-purple-600 hover:bg-purple-700">
            Actualizar
          </x-button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
