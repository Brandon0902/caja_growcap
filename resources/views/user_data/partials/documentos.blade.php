{{-- resources/views/user_data/partials/laborales.blade.php --}}
@php
  $laboral = $userData->laboral;
@endphp

<div 
  x-data="{
    companies: @json($empresas->mapWithKeys(fn($e) => [
      $e->id => ['direccion' => $e->direccion, 'telefono' => $e->telefono]
    ])),
    selected: @json(old('empresa_id', $laboral->empresa_id ?? '')),
    direccion: @json(old('direccion',   $laboral->direccion   ?? '')),
    telefono:  @json(old('telefono',    $laboral->telefono    ?? ''))
  }"
  x-init="
    if (selected) {
      direccion = companies[selected]?.direccion || '';
      telefono  = companies[selected]?.telefono  || '';
    }
  "
  x-cloak
  class="p-6 bg-gray-50 dark:bg-gray-700 rounded-b-lg"
>
  @if(session('success'))
    <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">
      {{ session('success') }}
    </div>
  @endif

  <form method="POST"
        action="{{ $laboral
                      ? route('user_data.laborales.update', [$userData, $laboral])
                      : route('user_data.laborales.store',  $userData) }}">
    @csrf
    @if($laboral) @method('PUT') @endif

    {{-- Ocultos para enviar los valores autocompletados --}}
    <input type="hidden" name="empresa_id"    x-bind:value="selected">
    <input type="hidden" name="direccion"     x-bind:value="direccion">
    <input type="hidden" name="telefono"      x-bind:value="telefono">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      {{-- Empresa --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Empresa</label>
        <select x-model="selected"
                @change="direccion = companies[selected]?.direccion || ''; telefono = companies[selected]?.telefono || ''"
                class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:ring-indigo-500 focus:border-indigo-500">
          <option value="">-- Seleccionar --</option>
          @foreach($empresas as $emp)
            <option value="{{ $emp->id }}"
              {{ old('empresa_id', $laboral->empresa_id ?? '') == $emp->id ? 'selected' : '' }}>
              {{ $emp->nombre }}
            </option>
          @endforeach
        </select>
        @error('empresa_id')
          <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
      </div>

      {{-- Dirección --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Dirección</label>
        <input type="text" disabled
               x-model="direccion"
               class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 focus:ring-indigo-500 focus:border-indigo-500" />
      </div>

      {{-- Teléfono --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Teléfono</label>
        <input type="text" disabled
               x-model="telefono"
               class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 focus:ring-indigo-500 focus:border-indigo-500" />
      </div>

      {{-- (Aquí siguen el resto de campos: puesto, salario, tipo salario, etc.) --}}

      {{-- Fecha Registro (ocupa ambas columnas) --}}
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Fecha Registro</label>
        <input 
          type="datetime-local" 
          name="fecha_registro"
          value="{{ old(
            'fecha_registro',
            optional($laboral->fecha_registro)->format('Y-m-d\TH:i')
          ) }}"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:ring-indigo-500 focus:border-indigo-500"
        />
        @error('fecha_registro')
          <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
      </div>
    </div>

    <div class="mt-6 flex space-x-2">
      <button type="submit"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">
        {{ $laboral ? 'Actualizar' : 'Guardar' }}
      </button>

      @if($laboral)
        <form action="{{ route('user_data.laborales.destroy', [$userData, $laboral]) }}"
              method="POST"
              onsubmit="return confirm('¿Eliminar registro laboral?');">
          @csrf
          @method('DELETE')
          <button type="submit"
                  class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">
            Eliminar
          </button>
        </form>
      @endif
    </div>
  </form>
</div>

{{-- Asegúrate de tener Alpine.js cargado en tu layout: --}}
{{-- <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script> --}}
