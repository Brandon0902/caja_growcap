{{-- resources/views/user_data/partials/laborales.blade.php --}}
@php
  $laboral = $userData->laboral;

  $mapTipo = ['Asalariado'=>10,'Independiente'=>7,'No hay datos'=>5];
  $mapRec  = ['Semanal'=>433,'Quincenal'=>200,'Mensual'=>100];
@endphp

<div
  x-data='{
    companies: @json(
      $empresas->mapWithKeys(fn($e) => [
        $e->id => ["direccion" => $e->direccion, "telefono" => $e->telefono]
      ])
    ),
    selected:  @json(old("empresa_id",  $laboral?->empresa_id  ?? "")),
    direccion: @json(old("direccion",   $laboral?->direccion   ?? "")),
    telefono:  @json(old("telefono",    $laboral?->telefono    ?? "")),
    tipoSalario: @json(old("tipo_salario", $laboral?->tipo_salario ?? "Asalariado")),
    recurrencia: @json(old("recurrencia_pago", $laboral?->recurrencia_pago ?? "Mensual")),
    mapTipo: @json($mapTipo),
    mapRec:  @json($mapRec),
    get tipoValor(){ return this.mapTipo[this.tipoSalario] ?? 0 },
    get recValor(){ return this.mapRec[this.recurrencia] ?? 0 },
  }'
  x-init="if (selected) { direccion = companies[selected]?.direccion || ''; telefono = companies[selected]?.telefono || '' }"
  x-cloak
  class="p-6 bg-gray-50 dark:bg-gray-700 rounded-b-lg"
>
  @if(session('success'))
    <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">
      {{ session('success') }}
    </div>
  @endif

  {{-- ===== Form Guardar/Actualizar ===== --}}
  <form
    method="POST"
    action="{{ $laboral
      ? route('user_data.laborales.update', ['userData' => $userData->id, 'laboral' => $laboral->id])
      : route('user_data.laborales.store',  ['userData' => $userData->id]) }}"
    class="space-y-6"
  >
    @csrf
    @if($laboral) @method('PUT') @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Empresa</label>
        <select
          x-model="selected"
          name="empresa_id"
          @change="direccion = companies[selected]?.direccion || ''; telefono = companies[selected]?.telefono || ''"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600
                 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                 focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option value="">-- Seleccionar --</option>
          @foreach($empresas as $emp)
            <option value="{{ $emp->id }}"
              {{ old('empresa_id', $laboral?->empresa_id ?? '') == $emp->id ? 'selected' : '' }}>
              {{ $emp->nombre }}
            </option>
          @endforeach
        </select>
        @error('empresa_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Puesto</label>
        <input type="text" name="puesto"
          value="{{ old('puesto', $laboral?->puesto ?? '') }}"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500" />
        @error('puesto') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Dirección</label>
        <input type="text" x-model="direccion" disabled
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600
                 text-gray-900 dark:text-gray-100" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Teléfono</label>
        <input type="text" x-model="telefono" disabled
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600
                 text-gray-900 dark:text-gray-100" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Salario Mensual</label>
        <input type="number" step="0.01" name="salario_mensual"
          value="{{ old('salario_mensual', $laboral?->salario_mensual ?? '') }}"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500" />
        @error('salario_mensual') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tipo Salario</label>
        <select name="tipo_salario" x-model="tipoSalario"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
          @foreach(['Asalariado','Independiente','No hay datos'] as $t)
            <option value="{{ $t }}" {{ old('tipo_salario', $laboral?->tipo_salario ?? '') === $t ? 'selected' : '' }}>
              {{ $t }}
            </option>
          @endforeach
        </select>
        @error('tipo_salario') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Valor Tipo Salario</label>
        <input type="number" :value="tipoValor" disabled
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600
                 text-gray-900 dark:text-gray-100" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Estado Salario</label>
        <select name="estado_salario"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
          @foreach(['Estable','Variable','Inestable'] as $e)
            <option value="{{ $e }}" {{ old('estado_salario', $laboral?->estado_salario ?? '') === $e ? 'selected' : '' }}>
              {{ $e }}
            </option>
          @endforeach
        </select>
        @error('estado_salario') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Recurrencia Pago</label>
        <select name="recurrencia_pago" x-model="recurrencia"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500">
          @foreach(['Semanal','Quincenal','Mensual'] as $r)
            <option value="{{ $r }}" {{ old('recurrencia_pago', $laboral?->recurrencia_pago ?? '') === $r ? 'selected' : '' }}>
              {{ $r }}
            </option>
          @endforeach
        </select>
        @error('recurrencia_pago') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Valor Recurrencia</label>
        <input type="number" :value="recValor" disabled
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600
                 text-gray-900 dark:text-gray-100" />
      </div>

      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Fecha Registro</label>
        <input type="datetime-local" name="fecha_registro"
          value="{{ old('fecha_registro', optional($laboral?->fecha_registro)->format('Y-m-d\TH:i')) }}"
          class="mt-1 block w-full rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                 text-gray-900 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500" />
        @error('fecha_registro') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
      </div>
    </div>

    <div class="mt-6 flex gap-2">
      <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">
        {{ $laboral ? 'Actualizar' : 'Guardar' }}
      </button>
    </div>
  </form>

  @if($laboral)
    <form
      action="{{ route('user_data.laborales.destroy', ['userData' => $userData->id, 'laboral' => $laboral->id]) }}"
      method="POST"
      class="mt-3"
      onsubmit="return confirm('¿Eliminar registro laboral?');"
    >
      @csrf
      @method('DELETE')
      <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">
        Eliminar
      </button>
    </form>
  @endif
</div>
