{{-- resources/views/movimientos-caja/create.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Nuevo Movimiento de Caja') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{ tipo: '{{ old('tipo_mov', 'ingreso') }}' }">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

      <x-validation-errors class="mb-4"/>

      <form action="{{ route('movimientos-caja.store') }}" method="POST">
        @csrf

        <div class="grid gap-6">
          {{-- Caja --}}
          <div>
            <label for="id_caja" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Caja
            </label>
            <select name="id_caja" id="id_caja"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                           focus:border-purple-500 focus:ring-purple-500
                           dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
              <option value="">{{ __('— Seleccionar caja —') }}</option>
              @foreach($cajas as $c)
                <option value="{{ $c->id_caja }}"
                  {{ old('id_caja') == $c->id_caja ? 'selected' : '' }}>
                  {{ $c->nombre }} ({{ $c->sucursal->nombre }})
                </option>
              @endforeach
            </select>
            @error('id_caja')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          {{-- Tipo de Movimiento --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Tipo de Movimiento
            </label>
            <div class="mt-1 flex items-center space-x-4">
              <label class="inline-flex items-center">
                <input type="radio" name="tipo_mov" value="ingreso" x-model="tipo"
                  {{ old('tipo_mov', 'ingreso') == 'ingreso' ? 'checked' : '' }}>
                <span class="ml-2">Ingreso</span>
              </label>
              <label class="inline-flex items-center">
                <input type="radio" name="tipo_mov" value="gasto" x-model="tipo"
                  {{ old('tipo_mov') == 'gasto' ? 'checked' : '' }}>
                <span class="ml-2">Gasto</span>
              </label>
            </div>
            @error('tipo_mov')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          {{-- Bloque de Ingreso --}}
          <div x-show="tipo==='ingreso'">
            {{-- Categoría Ingreso --}}
            <div>
              <label for="id_cat_ing" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Categoría de Ingreso
              </label>
              <select name="id_cat_ing" id="id_cat_ing"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                             focus:border-purple-500 focus:ring-purple-500
                             dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                <option value="">{{ __('— Seleccionar categoría —') }}</option>
                @foreach($catsIngreso as $cat)
                  <option value="{{ $cat->id_cat_ing }}"
                    {{ old('id_cat_ing') == $cat->id_cat_ing ? 'selected' : '' }}>
                    {{ $cat->nombre }}
                  </option>
                @endforeach
              </select>
              @error('id_cat_ing')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>

            {{-- Subcategoría Ingreso --}}
            <div>
              <label for="id_sub_ing" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Subcategoría de Ingreso
              </label>
              <select name="id_sub_ing" id="id_sub_ing"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                             focus:border-purple-500 focus:ring-purple-500
                             dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                <option value="">{{ __('— Seleccionar subcategoría —') }}</option>
                @foreach($subsIngreso as $sub)
                  <option value="{{ $sub->id_sub_ingreso }}"
                    {{ old('id_sub_ing') == $sub->id_sub_ingreso ? 'selected' : '' }}>
                    {{ $sub->nombre }}
                  </option>
                @endforeach
              </select>
              @error('id_sub_ing')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>
          </div>

          {{-- Bloque de Gasto --}}
          <div x-show="tipo==='gasto'">
            {{-- Categoría Gasto --}}
            <div>
              <label for="id_cat_gasto" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Categoría de Gasto
              </label>
              <select name="id_cat_gasto" id="id_cat_gasto"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                             focus:border-purple-500 focus:ring-purple-500
                             dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                <option value="">{{ __('— Seleccionar categoría —') }}</option>
                @foreach($catsGasto as $cat)
                  <option value="{{ $cat->id_cat_gasto }}"
                    {{ old('id_cat_gasto') == $cat->id_cat_gasto ? 'selected' : '' }}>
                    {{ $cat->nombre }}
                  </option>
                @endforeach
              </select>
              @error('id_cat_gasto')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>

            {{-- Subcategoría Gasto --}}
            <div>
              <label for="id_sub_gasto" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Subcategoría de Gasto
              </label>
              <select name="id_sub_gasto" id="id_sub_gasto"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                             focus:border-purple-500 focus:ring-purple-500
                             dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                <option value="">{{ __('— Seleccionar subcategoría —') }}</option>
                @foreach($subsGasto as $sub)
                  <option value="{{ $sub->id_sub_gasto }}"
                    {{ old('id_sub_gasto') == $sub->id_sub_gasto ? 'selected' : '' }}>
                    {{ $sub->nombre }}
                  </option>
                @endforeach
              </select>
              @error('id_sub_gasto')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>
          </div>

          {{-- Proveedor (SIEMPRE visible) --}}
          <div>
            <label for="proveedor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Proveedor (opcional)
            </label>
            <div class="mt-1 flex items-end gap-2">
              <div class="flex-1">
                <select name="proveedor_id" id="proveedor_id"
                        class="block w-full rounded-md border-gray-300 shadow-sm
                               focus:border-purple-500 focus:ring-purple-500
                               dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                  <option value="">{{ __('— Seleccionar proveedor —') }}</option>
                  @foreach($proveedores as $prov)
                    <option value="{{ $prov->id_proveedor }}"
                      {{ old('proveedor_id', session('nuevo_proveedor_id')) == $prov->id_proveedor ? 'selected' : '' }}>
                      {{ $prov->nombre }}
                    </option>
                  @endforeach
                </select>
              </div>
              <a href="{{ route('proveedores.create', ['back' => request()->fullUrl()]) }}"
                 class="inline-flex items-center px-3 py-2 rounded bg-purple-600 text-white hover:bg-purple-700">
                 + Agregar proveedor
              </a>
            </div>
            @error('proveedor_id')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          {{-- Monto --}}
          <div>
            <label for="monto" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Monto
            </label>
            <input type="number" name="monto" id="monto" step="0.01"
                   value="{{ old('monto') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                          focus:border-purple-500 focus:ring-purple-500
                          dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" required>
            @error('monto')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          {{-- Fecha --}}
          <div>
            <label for="fecha" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Fecha
            </label>
            <input type="date" name="fecha" id="fecha"
                   value="{{ old('fecha', now()->format('Y-m-d')) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                          focus:border-purple-500 focus:ring-purple-500
                          dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
            @error('fecha')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>

          {{-- Descripción --}}
          <div>
            <label for="descripcion" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              Descripción
            </label>
            <textarea name="descripcion" id="descripcion" rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                             focus:border-purple-500 focus:ring-purple-500
                             dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">{{ old('descripcion') }}</textarea>
            @error('descripcion')
              <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <div class="mt-6 flex justify-end space-x-2">
          <a href="{{ route('movimientos-caja.index') }}"
             class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
            Cancelar
          </a>
          <button type="submit"
                  class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
            Registrar movimiento
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
