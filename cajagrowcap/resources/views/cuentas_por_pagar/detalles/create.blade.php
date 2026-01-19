{{-- resources/views/cuentas_por_pagar/detalles/create.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white">
      {{ __('Agregar Pago a Cuenta #') . $cuentas_por_pagar->id_cuentas_por_pagar }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
      <form action="{{ route('cuentas-por-pagar.detalles.store', $cuentas_por_pagar) }}"
            method="POST">
        @csrf
        <div class="grid grid-cols-1 gap-6">

          {{-- Número de Pago --}}
          <div>
            <label for="numero_pago" class="block text-sm font-medium">{{ __('# Pago') }}</label>
            <input type="number" name="numero_pago" id="numero_pago"
                   value="{{ old('numero_pago') }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('numero_pago')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Fecha de Pago --}}
          <div>
            <label for="fecha_pago" class="block text-sm font-medium">{{ __('Fecha de Pago') }}</label>
            <input type="date" name="fecha_pago" id="fecha_pago"
                   value="{{ old('fecha_pago') }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('fecha_pago')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Saldo Inicial --}}
          <div>
            <label for="saldo_inicial" class="block text-sm font-medium">{{ __('Saldo Inicial') }}</label>
            <input type="number" step="0.01" name="saldo_inicial" id="saldo_inicial"
                   value="{{ old('saldo_inicial') }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('saldo_inicial')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Amortización de Capital --}}
          <div>
            <label for="amortizacion_cap" class="block text-sm font-medium">{{ __('Amortización Capital') }}</label>
            <input type="number" step="0.01" name="amortizacion_cap" id="amortizacion_cap"
                   value="{{ old('amortizacion_cap') }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('amortizacion_cap')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Pago de Interés --}}
          <div>
            <label for="pago_interes" class="block text-sm font-medium">{{ __('Pago Interés') }}</label>
            <input type="number" step="0.01" name="pago_interes" id="pago_interes"
                   value="{{ old('pago_interes') }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('pago_interes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Monto Pago --}}
          <div>
            <label for="monto_pago" class="block text-sm font-medium">{{ __('Total Pago') }}</label>
            <input type="number" step="0.01" name="monto_pago" id="monto_pago"
                   value="{{ old('monto_pago') }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('monto_pago')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Saldo Restante --}}
          <div>
            <label for="saldo_restante" class="block text-sm font-medium">{{ __('Saldo Restante') }}</label>
            <input type="number" step="0.01" name="saldo_restante" id="saldo_restante"
                   value="{{ old('saldo_restante') }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('saldo_restante')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Semana --}}
          <div>
            <label for="semana" class="block text-sm font-medium">{{ __('Semana') }}</label>
            <input type="number" name="semana" id="semana"
                   value="{{ old('semana') }}"
                   class="mt-1 block w-full rounded-md"/>
            @error('semana')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Caja --}}
          <div>
            <label for="caja_id" class="block text-sm font-medium">{{ __('Caja') }}</label>
            <select name="caja_id" id="caja_id" class="mt-1 block w-full rounded-md">
              <option value="">{{ __('-- Selecciona Caja --') }}</option>
              @foreach($cajas as $c)
                <option value="{{ $c->id_caja }}"
                  {{ old('caja_id') == $c->id_caja ? 'selected' : '' }}>
                  {{ $c->nombre }}
                </option>
              @endforeach
            </select>
            @error('caja_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Estado --}}
          <div>
            <label for="estado" class="block text-sm font-medium">{{ __('Estado') }}</label>
            <select name="estado" id="estado" class="mt-1 block w-full rounded-md">
              <option value="pendiente" {{ old('estado')=='pendiente'?'selected':'' }}>
                {{ __('Pendiente') }}
              </option>
              <option value="pagado" {{ old('estado')=='pagado'?'selected':'' }}>
                {{ __('Pagado') }}
              </option>
              <option value="vencido" {{ old('estado')=='vencido'?'selected':'' }}>
                {{ __('Vencido') }}
              </option>
            </select>
            @error('estado')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
          </div>

          {{-- Botón Guardar --}}
          <div class="pt-4">
            <button type="submit"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
              {{ __('Guardar Pago') }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
