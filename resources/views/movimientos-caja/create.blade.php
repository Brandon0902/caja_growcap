{{-- resources/views/movimientos_caja/create.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Nuevo Movimiento de Caja') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8"
       x-data="{ tipo: '{{ old('tipo_mov','ingreso') }}' }"
  >
    <x-validation-errors class="mb-4"/>

    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
      <form action="{{ route('movimientos-caja.store') }}" method="POST">
        @csrf

        {{-- Caja --}}
        <div class="mb-4">
          <x-label for="id_caja" value="Caja" />
          <select id="id_caja" name="id_caja"
                  class="mt-1 block w-full border rounded px-3 py-2
                         bg-white dark:bg-gray-700 dark:text-gray-200
                         focus:ring-purple-500">
            <option value="">{{ __('— Seleccionar caja —') }}</option>
            @foreach($cajas as $c)
              <option value="{{ $c->id_caja }}"
                {{ old('id_caja')==$c->id_caja ? 'selected':'' }}>
                {{ $c->nombre }} ({{ $c->sucursal->nombre }})
              </option>
            @endforeach
          </select>
        </div>

        {{-- Tipo de movimiento --}}
        <div class="mb-4">
          <x-label for="tipo_mov" value="Tipo" />
          <select id="tipo_mov" name="tipo_mov" x-model="tipo"
                  class="mt-1 block w-full border rounded px-3 py-2
                         bg-white dark:bg-gray-700 dark:text-gray-200
                         focus:ring-purple-500">
            <option value="ingreso">{{ __('Ingreso') }}</option>
            <option value="gasto">{{ __('Gasto') }}</option>
          </select>
        </div>

        {{-- Categorías dinámicas --}}
        <div x-show="tipo==='ingreso'">
          <div class="mb-4">
            <x-label for="id_cat_ing" value="Categoría de Ingreso" />
            <select id="id_cat_ing" name="id_cat_ing"
                    class="mt-1 block w-full border rounded px-3 py-2">
              <option value="">{{ __('— Seleccionar categoría —') }}</option>
              @foreach($catsIngreso as $cat)
                <option value="{{ $cat->id_cat_ing }}"
                  {{ old('id_cat_ing')==$cat->id_cat_ing ? 'selected':'' }}>
                  {{ $cat->nombre }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="mb-4">
            <x-label for="id_sub_ing" value="Subcategoría de Ingreso" />
            <select id="id_sub_ing" name="id_sub_ing"
                    class="mt-1 block w-full border rounded px-3 py-2">
              <option value="">{{ __('— Seleccionar subcategoría —') }}</option>
              @foreach($subsIngreso as $sub)
                <option value="{{ $sub->id_sub_ingreso }}"
                  {{ old('id_sub_ing')==$sub->id_sub_ingreso ? 'selected':'' }}>
                  {{ $sub->nombre }}
                </option>
              @endforeach
            </select>
          </div>
        </div>

        <div x-show="tipo==='gasto'">
          <div class="mb-4">
            <x-label for="id_cat_gasto" value="Categoría de Gasto" />
            <select id="id_cat_gasto" name="id_cat_gasto"
                    class="mt-1 block w-full border rounded px-3 py-2">
              <option value="">{{ __('— Seleccionar categoría —') }}</option>
              @foreach($catsGasto as $cat)
                <option value="{{ $cat->id_cat_gasto }}"
                  {{ old('id_cat_gasto')==$cat->id_cat_gasto ? 'selected':'' }}>
                  {{ $cat->nombre }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="mb-4">
            <x-label for="id_sub_gasto" value="Subcategoría de Gasto" />
            <select id="id_sub_gasto" name="id_sub_gasto"
                    class="mt-1 block w-full border rounded px-3 py-2">
              <option value="">{{ __('— Seleccionar subcategoría —') }}</option>
              @foreach($subsGasto as $sub)
                <option value="{{ $sub->id_sub_gasto }}"
                  {{ old('id_sub_gasto')==$sub->id_sub_gasto ? 'selected':'' }}>
                  {{ $sub->nombre }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="mb-4">
            <x-label for="proveedor_id" value="Proveedor (si aplica)" />
            <select id="proveedor_id" name="proveedor_id"
                    class="mt-1 block w-full border rounded px-3 py-2">
              <option value="">{{ __('— Seleccionar proveedor —') }}</option>
              @foreach($proveedores as $p)
                <option value="{{ $p->id_proveedor }}"
                  {{ old('proveedor_id')==$p->id_proveedor ? 'selected':'' }}>
                  {{ $p->nombre }}
                </option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Monto, fecha y descripción --}}
        <div class="mb-4">
          <x-label for="monto" value="Monto" />
          <x-input id="monto" name="monto" type="number" step="0.01"
                   :value="old('monto')" required />
        </div>

        <div class="mb-4">
          <x-label for="fecha" value="Fecha" />
          <x-input id="fecha" name="fecha" type="datetime-local"
                   :value="old('fecha', now()->format('Y-m-d\TH:i'))" />
        </div>

        <div class="mb-6">
          <x-label for="descripcion" value="Descripción" />
          <textarea id="descripcion" name="descripcion"
                    class="mt-1 block w-full border rounded px-3 py-2"
                    rows="3">{{ old('descripcion') }}</textarea>
        </div>

        <div class="flex justify-end space-x-3">
          <a href="{{ route('movimientos-caja.index') }}"
             class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded">
            {{ __('Cancelar') }}
          </a>
          <x-button type="submit" class="bg-purple-600 hover:bg-purple-700">
            {{ __('Registrar movimiento') }}
          </x-button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
