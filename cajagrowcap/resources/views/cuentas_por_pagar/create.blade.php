<!-- resources/views/cuentas_por_pagar/create.blade.php -->
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white">{{ __('Crear Cuenta por Pagar') }}</h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
      <form action="{{ route('cuentas-por-pagar.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 gap-6">

          {{-- Sucursal --}}
          <div>
            <label for="id_sucursal" class="block text-sm font-medium">{{ __('Sucursal') }}</label>
            <select name="id_sucursal" id="id_sucursal" class="mt-1 block w-full rounded-md">
              <option value="">{{ __('-- Selecciona Sucursal --') }}</option>
              @foreach($sucursales as $s)
                <option value="{{ $s->id_sucursal }}" {{ old('id_sucursal') == $s->id_sucursal ? 'selected' : '' }}>
                  {{ $s->nombre }}
                </option>
              @endforeach
            </select>
            @error('id_sucursal')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Caja --}}
          <div>
            <label for="id_caja" class="block text-sm font-medium">{{ __('Caja') }}</label>
            <select name="id_caja" id="id_caja" class="mt-1 block w-full rounded-md">
              <option value="">{{ __('-- Selecciona Caja --') }}</option>
              @foreach($cajas as $c)
                <option value="{{ $c->id_caja }}" {{ old('id_caja') == $c->id_caja ? 'selected' : '' }}>
                  {{ $c->nombre }}
                </option>
              @endforeach
            </select>
            @error('id_caja')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Proveedor --}}
          <div>
            <label for="proveedor_id" class="block text-sm font-medium">{{ __('Proveedor') }}</label>
            <select name="proveedor_id" id="proveedor_id" class="mt-1 block w-full rounded-md">
              <option value="">{{ __('-- Selecciona Proveedor --') }}</option>
              @foreach($proveedores as $p)
                <option value="{{ $p->id_proveedor }}" {{ old('proveedor_id') == $p->id_proveedor ? 'selected' : '' }}>
                  {{ $p->nombre }}
                </option>
              @endforeach
            </select>
            @error('proveedor_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Monto Total --}}
          <div>
            <label for="monto_total" class="block text-sm font-medium">{{ __('Monto Total') }}</label>
            <input type="number" step="0.01" name="monto_total" id="monto_total"
                   value="{{ old('monto_total') }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('monto_total')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Tasa anual (%) --}}
          <div>
            <label for="tasa_anual" class="block text-sm font-medium">{{ __('Tasa anual (%)') }}</label>
            <input type="number" step="0.01" name="tasa_anual" id="tasa_anual"
                   value="{{ old('tasa_anual') }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('tasa_anual')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Número de abonos --}}
          <div>
            <label for="numero_abonos" class="block text-sm font-medium">{{ __('Número de abonos') }}</label>
            <input type="number" name="numero_abonos" id="numero_abonos"
                   value="{{ old('numero_abonos') }}"
                   class="mt-1 block w-full rounded-md" min="1"/>
            @error('numero_abonos')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Periodo de pago --}}
          <div>
            <label for="periodo_pago" class="block text-sm font-medium">{{ __('Periodo de pago') }}</label>
            <select name="periodo_pago" id="periodo_pago" class="mt-1 block w-full rounded-md">
              <option value="">{{ __('-- Selecciona Periodo --') }}</option>
              <option value="semanal"   {{ old('periodo_pago') == 'semanal'   ? 'selected' : '' }}>{{ __('Semanal') }}</option>
              <option value="quincenal" {{ old('periodo_pago') == 'quincenal' ? 'selected' : '' }}>{{ __('Quincenal') }}</option>
              <option value="mensual"   {{ old('periodo_pago') == 'mensual'   ? 'selected' : '' }}>{{ __('Mensual') }}</option>
            </select>
            @error('periodo_pago')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Fecha Emisión --}}
          <div>
            <label for="fecha_emision" class="block text-sm font-medium">{{ __('Fecha de Emisión') }}</label>
            <input type="date" name="fecha_emision" id="fecha_emision"
                   value="{{ old('fecha_emision') }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('fecha_emision')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Fecha Vencimiento (auto) --}}
          <div>
            <label for="fecha_vencimiento" class="block text-sm font-medium">{{ __('Fecha de Vencimiento') }}</label>
            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento"
                   value="{{ old('fecha_vencimiento') }}"
                   class="mt-1 block w-full rounded-md"
                   readonly />
            @error('fecha_vencimiento')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Estado --}}
          <div>
            <label for="estado" class="block text-sm font-medium">{{ __('Estado') }}</label>
            <select name="estado" id="estado" class="mt-1 block w-full rounded-md">
              <option value="pendiente" {{ old('estado') == 'pendiente' ? 'selected' : '' }}>{{ __('Pendiente') }}</option>
              <option value="pagado"    {{ old('estado') == 'pagado'    ? 'selected' : '' }}>{{ __('Pagado') }}</option>
              <option value="vencido"   {{ old('estado') == 'vencido'   ? 'selected' : '' }}>{{ __('Vencido') }}</option>
            </select>
            @error('estado')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Descripción --}}
          <div>
            <label for="descripcion" class="block text-sm font-medium">{{ __('Descripción') }}</label>
            <textarea name="descripcion" id="descripcion" rows="3" class="mt-1 block w-full rounded-md">{{ old('descripcion') }}</textarea>
            @error('descripcion')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Botón Guardar --}}
          <div class="pt-4">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
              {{ __('Guardar') }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- Script para calcular fecha de vencimiento automáticamente --}}
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      const numAbonos    = document.getElementById('numero_abonos');
      const periodo      = document.getElementById('periodo_pago');
      const fechaEmi     = document.getElementById('fecha_emision');
      const fechaVenci   = document.getElementById('fecha_vencimiento');

      function calcularVencimiento() {
        const n   = parseInt(numAbonos.value);
        const per = periodo.value;
        const fe  = fechaEmi.value;
        if (!n || !per || !fe) return;

        let fecha = new Date(fe);

        switch(per){
          case 'semanal':
            fecha.setDate(fecha.getDate() + 7 * n);
            break;
          case 'quincenal':
            fecha.setDate(fecha.getDate() + 15 * n);
            break;
          case 'mensual':
            fecha.setMonth(fecha.getMonth() + n);
            break;
        }

        const yyyy = fecha.getFullYear();
        const mm   = String(fecha.getMonth() + 1).padStart(2, '0');
        const dd   = String(fecha.getDate()).padStart(2, '0');

        fechaVenci.value = `${yyyy}-${mm}-${dd}`;
      }

      numAbonos.addEventListener('input',    calcularVencimiento);
      periodo  .addEventListener('change',   calcularVencimiento);
      fechaEmi .addEventListener('change',   calcularVencimiento);
    });
  </script>
</x-app-layout>
