<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white">{{ __('Editar Cuenta por Pagar') }}</h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
      <form action="{{ route('cuentas-por-pagar.update', $cuenta) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6">

          {{-- Sucursal --}}
          <div>
            <label for="id_sucursal" class="block text-sm font-medium">{{ __('Sucursal') }}</label>
            <select name="id_sucursal" id="id_sucursal" class="mt-1 block w-full rounded-md">
              <option value="">{{ __('-- Selecciona Sucursal --') }}</option>
              @foreach($sucursales as $s)
                <option value="{{ $s->id_sucursal }}" {{ old('id_sucursal', $cuenta->id_sucursal) == $s->id_sucursal ? 'selected' : '' }}>
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
                <option value="{{ $c->id_caja }}" {{ old('id_caja', $cuenta->id_caja) == $c->id_caja ? 'selected' : '' }}>
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
                <option value="{{ $p->id_proveedor }}" {{ old('proveedor_id', $cuenta->proveedor_id) == $p->id_proveedor ? 'selected' : '' }}>
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
                   value="{{ old('monto_total', $cuenta->monto_total) }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('monto_total')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Tasa anual (%) --}}
          <div>
            <label for="tasa_anual" class="block text-sm font-medium">{{ __('Tasa anual (%)') }}</label>
            <input type="number" step="0.01" name="tasa_anual" id="tasa_anual"
                   value="{{ old('tasa_anual', $cuenta->tasa_anual) }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('tasa_anual')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Número de abonos --}}
          <div>
            <label for="numero_abonos" class="block text-sm font-medium">{{ __('Número de abonos') }}</label>
            <input type="number" name="numero_abonos" id="numero_abonos"
                   value="{{ old('numero_abonos', $cuenta->numero_abonos) }}"
                   class="mt-1 block w-full rounded-md" min="1"/>
            @error('numero_abonos')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Periodo de pago --}}
          <div>
            <label for="periodo_pago" class="block text-sm font-medium">{{ __('Periodo de pago') }}</label>
            <select name="periodo_pago" id="periodo_pago" class="mt-1 block w-full rounded-md">
              <option value="semanal" {{ old('periodo_pago', $cuenta->periodo_pago) == 'semanal' ? 'selected' : '' }}>{{ __('Semanal') }}</option>
              <option value="quincenal" {{ old('periodo_pago', $cuenta->periodo_pago) == 'quincenal' ? 'selected' : '' }}>{{ __('Quincenal') }}</option>
              <option value="mensual" {{ old('periodo_pago', $cuenta->periodo_pago) == 'mensual' ? 'selected' : '' }}>{{ __('Mensual') }}</option>
            </select>
            @error('periodo_pago')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Fecha Emisión --}}
          <div>
            <label for="fecha_emision" class="block text-sm font-medium">{{ __('Fecha de Emisión') }}</label>
            <input type="date" name="fecha_emision" id="fecha_emision"
                   value="{{ old('fecha_emision', $cuenta->fecha_emision) }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('fecha_emision')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Fecha Vencimiento --}}
          <div>
            <label for="fecha_vencimiento" class="block text-sm font-medium">{{ __('Fecha de Vencimiento') }}</label>
            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento"
                   value="{{ old('fecha_vencimiento', $cuenta->fecha_vencimiento) }}"
                   class="mt-1 block w-full rounded-md" readonly/>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Se calcula automáticamente con base en emisión, periodo y número de abonos.</p>
            @error('fecha_vencimiento')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Estado --}}
          <div>
            <label for="estado" class="block text-sm font-medium">{{ __('Estado') }}</label>
            <select name="estado" id="estado" class="mt-1 block w-full rounded-md">
              <option value="pendiente" {{ old('estado', $cuenta->estado) == 'pendiente' ? 'selected' : '' }}>{{ __('Pendiente') }}</option>
              <option value="pagado"   {{ old('estado', $cuenta->estado) == 'pagado' ? 'selected' : '' }}>{{ __('Pagado') }}</option>
              <option value="vencido"  {{ old('estado', $cuenta->estado) == 'vencido' ? 'selected' : '' }}>{{ __('Vencido') }}</option>
            </select>
            @error('estado')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Descripción --}}
          <div>
            <label for="descripcion" class="block text-sm font-medium">{{ __('Descripción') }}</label>
            <textarea name="descripcion" id="descripcion" rows="3" class="mt-1 block w-full rounded-md">{{ old('descripcion', $cuenta->descripcion) }}</textarea>
            @error('descripcion')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Botones --}}
          <div class="pt-4 space-x-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">{{ __('Actualizar') }}</button>
            <a href="{{ route('cuentas-por-pagar.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">{{ __('Cancelar') }}</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const emision = document.getElementById('fecha_emision');
      const periodo = document.getElementById('periodo_pago');
      const abonos  = document.getElementById('numero_abonos');
      const venc    = document.getElementById('fecha_vencimiento');

      function pad(n){ return String(n).padStart(2,'0'); }
      function toYmd(d){ return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`; }
      function daysInMonth(y,m){ return new Date(y, m+1, 0).getDate(); }

      function addMonthsNoOverflow(date, n){
        const d = new Date(date.getTime());
        const day = d.getDate();
        d.setDate(1);
        d.setMonth(d.getMonth() + n);
        const max = daysInMonth(d.getFullYear(), d.getMonth());
        d.setDate(Math.min(day, max));
        return d;
      }

      function calcVencimiento() {
        if (!emision.value) return;
        const n = parseInt(abonos.value || '0', 10);
        if (!n || n < 1) return;

        const base = new Date(emision.value + 'T00:00:00');
        let out = new Date(base.getTime());

        if (periodo.value === 'semanal') {
          out.setDate(out.getDate() + (7 * n));
        } else if (periodo.value === 'quincenal') {
          out.setDate(out.getDate() + (15 * n));
        } else if (periodo.value === 'mensual') {
          out = addMonthsNoOverflow(out, n);
        }

        venc.value = toYmd(out);
      }

      [emision, periodo, abonos].forEach(el => el.addEventListener('change', calcVencimiento));
      abonos.addEventListener('input', calcVencimiento);

      calcVencimiento();
    });
  </script>
</x-app-layout>
