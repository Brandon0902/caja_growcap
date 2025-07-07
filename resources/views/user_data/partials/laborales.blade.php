{{-- resources/views/user_data/partials/laborales.blade.php --}}
@php
  $laboral = $userData->laboral;
@endphp

<div
  x-data='{
    companies: @json(
      $empresas->mapWithKeys(fn($e) => [
        $e->id => [
          "direccion" => $e->direccion,
          "telefono"  => $e->telefono
        ]
      ])
    ),
    selected:  @json(old("empresa_id",  $laboral?->empresa_id  ?? "")),
    direccion: @json(old("direccion",   $laboral?->direccion   ?? "")),
    telefono:  @json(old("telefono",    $laboral?->telefono    ?? ""))
  }'
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

  <form
    method="POST"
    action="{{ $laboral
      ? route('user_data.laborales.update', [$userData, $laboral])
      : route('user_data.laborales.store',  $userData) }}"
  >
    @csrf
    @if($laboral) @method('PUT') @endif

    {{-- Hidden para Alpine --}}
    <input type="hidden" name="empresa_id" x-bind:value="selected">
    <input type="hidden" name="direccion"   x-bind:value="direccion">
    <input type="hidden" name="telefono"    x-bind:value="telefono">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      {{-- Empresa --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Empresa</label>
        <select
          x-model="selected"
          @change="direccion = companies[selected]?.direccion || ''; telefono = companies[selected]?.telefono || ''"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600
                 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                 focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option value="">-- Seleccionar --</option>
          @foreach($empresas as $emp)
            <option
              value="{{ $emp->id }}"
              {{ old('empresa_id', $laboral?->empresa_id ?? '') == $emp->id ? 'selected' : '' }}
            >
              {{ $emp->nombre }}
            </option>
          @endforeach
        </select>
        @error('empresa_id')
          <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
      </div>

      {{-- Dirección (autocompletada) --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Dirección</label>
        <input
          type="text" disabled x-model="direccion"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600
                 bg-gray-100 dark:bg-gray-600 text-gray-900 dark:text-gray-100
                 focus:ring-indigo-500 focus:border-indigo-500"
        />
      </div>

      {{-- Teléfono (autocompletado) --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Teléfono</label>
        <input
          type="text" disabled x-model="telefono"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600
                 bg-gray-100 dark:bg-gray-600 text-gray-900 dark:text-gray-100
                 focus:ring-indigo-500 focus:border-indigo-500"
        />
      </div>

      {{-- Puesto --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Puesto</label>
        <input
          type="text" name="puesto"
          value="{{ old('puesto', $laboral?->puesto ?? '') }}"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600
                 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                 focus:ring-indigo-500 focus:border-indigo-500"
        />
        @error('puesto')
          <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
      </div>

      {{-- Salario Mensual --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Salario Mensual</label>
        <input
          type="number" step="0.01" name="salario_mensual"
          value="{{ old('salario_mensual', $laboral?->salario_mensual ?? '') }}"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600
                 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                 focus:ring-indigo-500 focus:border-indigo-500"
        />
        @error('salario_mensual')
          <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
      </div>

      {{-- Tipo Salario --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tipo Salario</label>
        <select
          name="tipo_salario"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600
                 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                 focus:ring-indigo-500 focus:border-indigo-500"
        >
          @foreach(['Asalariado','Independiente','No hay datos'] as $t)
            <option
              value="{{ $t }}"
              {{ old('tipo_salario', $laboral?->tipo_salario ?? '') === $t ? 'selected' : '' }}
            >
              {{ $t }}
            </option>
          @endforeach
        </select>
        @error('tipo_salario')
          <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
      </div>

      {{-- Estado Salario --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Estado Salario</label>
        <select
          name="estado_salario"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600
                 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                 focus:ring-indigo-500 focus:border-indigo-500"
        >
          @foreach(['Estable','Variable','Inestable'] as $e)
            <option
              value="{{ $e }}"
              {{ old('estado_salario', $laboral?->estado_salario ?? '') === $e ? 'selected' : '' }}
            >
              {{ $e }}
            </option>
          @endforeach
        </select>
        @error('estado_salario')
          <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
      </div>

      {{-- Valor Tipo Salario --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Valor Tipo Salario</label>
        <input
          type="number" name="tipo_salario_valor"
          value="{{ old('tipo_salario_valor', $laboral?->tipo_salario_valor ?? '') }}"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600
                 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                 focus:ring-indigo-500 focus:border-indigo-500"
        />
        @error('tipo_salario_valor')
          <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
      </div>

      {{-- Recurrencia Pago --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Recurrencia Pago</label>
        <select
          name="recurrencia_pago"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600
                 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                 focus:ring-indigo-500 focus:border-indigo-500"
        >
          @foreach(['Semanal','Quincenal','Mensual'] as $r)
            <option
              value="{{ $r }}"
              {{ old('recurrencia_pago', $laboral?->recurrencia_pago ?? '') === $r ? 'selected' : '' }}
            >
              {{ $r }}
            </option>
          @endforeach
        </select>
        @error('recurrencia_pago')
          <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
      </div>

      {{-- Valor Recurrencia --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Valor Recurrencia</label>
        <input
          type="number" name="recurrencia_valor"
          value="{{ old('recurrencia_valor', $laboral?->recurrencia_valor ?? '') }}"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600
                 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                 focus:ring-indigo-500 focus:border-indigo-500"
        />
        @error('recurrencia_valor')
          <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
      </div>

      {{-- Fecha Registro --}}
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Fecha Registro</label>
        <input
          type="datetime-local" name="fecha_registro"
          value="{{ old(
            'fecha_registro',
            optional($laboral?->fecha_registro)->format('Y-m-d\TH:i')
          ) }}"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600
                 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                 focus:ring-indigo-500 focus:border-indigo-500"
        />
        @error('fecha_registro')
          <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
      </div>
    </div>

    <div class="mt-6 flex space-x-2">
      <button
        type="submit"
        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded"
      >
        {{ $laboral ? 'Actualizar' : 'Guardar' }}
      </button>

      @if($laboral)
        <form
          action="{{ route('user_data.laborales.destroy', [$userData, $laboral]) }}"
          method="POST"
          onsubmit="return confirm('¿Eliminar registro laboral?');"
        >
          @csrf
          @method('DELETE')
          <button
            type="submit"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded"
          >
            Eliminar
          </button>
        </form>
      @endif
    </div>
  </form>
</div>
