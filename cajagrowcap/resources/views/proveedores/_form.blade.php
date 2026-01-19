@props(['proveedor' => null])

@php
  $p = $proveedor;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nombre *</label>
    <input name="nombre" type="text" required
           value="{{ old('nombre', $p->nombre ?? '') }}"
           class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                  focus:ring-purple-500 focus:border-purple-500 bg-white dark:bg-gray-700
                  text-gray-900 dark:text-gray-100">
    @error('nombre') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Email</label>
    <input name="email" type="email"
           value="{{ old('email', $p->email ?? '') }}"
           class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                  focus:ring-purple-500 focus:border-purple-500 bg-white dark:bg-gray-700
                  text-gray-900 dark:text-gray-100">
    @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Teléfono</label>
    <input name="telefono" type="text"
           value="{{ old('telefono', $p->telefono ?? '') }}"
           class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                  focus:ring-purple-500 focus:border-purple-500 bg-white dark:bg-gray-700
                  text-gray-900 dark:text-gray-100">
    @error('telefono') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contacto</label>
    <input name="contacto" type="text"
           value="{{ old('contacto', $p->contacto ?? '') }}"
           class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                  focus:ring-purple-500 focus:border-purple-500 bg-white dark:bg-gray-700
                  text-gray-900 dark:text-gray-100">
    @error('contacto') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div class="md:col-span-2">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Dirección</label>
    <input name="direccion" type="text"
           value="{{ old('direccion', $p->direccion ?? '') }}"
           class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                  focus:ring-purple-500 focus:border-purple-500 bg-white dark:bg-gray-700
                  text-gray-900 dark:text-gray-100">
    @error('direccion') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Estado *</label>
    <select name="estado" required
            class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm
                   focus:ring-purple-500 focus:border-purple-500 bg-white dark:bg-gray-700
                   text-gray-900 dark:text-gray-100">
      <option value="activo"   @selected(old('estado', $p->estado ?? 'activo') === 'activo')>Activo</option>
      <option value="inactivo" @selected(old('estado', $p->estado ?? '') === 'inactivo')>Inactivo</option>
    </select>
    @error('estado') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>
</div>
