{{-- resources/views/cuentas_por_pagar/show.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white">
      {{ __('Cuenta #') . $cuenta->id_cuentas_por_pagar }}
    </h2>
  </x-slot>

  {{-- Asegura que el cloak exista aquí o, mejor, muévelo a tu layout global --}}
  <style>[x-cloak]{ display:none !important; }</style>

  <div class="py-6 mx-auto max-w-5xl px-4 space-y-6">

    {{-- Cabecera con datos de la cuenta --}}
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 grid grid-cols-2 gap-4">
      <div><strong>Sucursal:</strong> {{ optional($cuenta->sucursal)->nombre ?? '–' }}</div>
      <div><strong>Caja:</strong>     {{ optional($cuenta->caja)->nombre    ?? '–' }}</div>
      <div><strong>Proveedor:</strong>{{ optional($cuenta->proveedor)->nombre ?? '–' }}</div>
      <div><strong>Monto Total:</strong>{{ number_format($cuenta->monto_total, 2) }}</div>
      <div><strong>Tasa anual:</strong> {{ $cuenta->tasa_anual }}%</div>
      <div><strong># de abonos:</strong> {{ $cuenta->numero_abonos }}</div>
      <div><strong>Periodo:</strong>    {{ ucfirst($cuenta->periodo_pago) }}</div>
      <div><strong>Emisión:</strong>    {{ $cuenta->fecha_emision->format('Y-m-d') }}</div>
      <div><strong>Vencimiento:</strong>{{ $cuenta->fecha_vencimiento->format('Y-m-d') }}</div>
      <div><strong>Estado:</strong>     {{ ucfirst($cuenta->estado) }}</div>
      <div class="col-span-2"><strong>Descripción:</strong> {{ $cuenta->descripcion ?? '–' }}</div>
    </div>

    {{-- Cronograma de amortización y modal de pago --}}
    <div 
      x-data="{ modalPagoId: null }"
      class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 overflow-x-auto"
    >
      <h3 class="font-semibold mb-4">{{ __('Cronograma de amortización') }}</h3>

      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-4 py-2">#</th>
            <th class="px-4 py-2">Fecha</th>
            <th class="px-4 py-2 text-right">Saldo inicial</th>
            <th class="px-4 py-2 text-right">Capital</th>
            <th class="px-4 py-2 text-right">Interés</th>
            <th class="px-4 py-2 text-right">Total pago</th>
            <th class="px-4 py-2 text-right">Saldo restante</th>
            <th class="px-4 py-2">Estado</th>
            <th class="px-4 py-2">Acción</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($cuenta->detalles as $d)
            <tr class="{{ $d->estado === 'vencido' ? 'bg-red-50' : '' }}">
              <td class="px-4 py-2 text-center">{{ $d->numero_pago }}</td>
              <td class="px-4 py-2">{{ $d->fecha_pago->format('Y-m-d') }}</td>
              <td class="px-4 py-2 text-right">{{ number_format($d->saldo_inicial,2) }}</td>
              <td class="px-4 py-2 text-right">{{ number_format($d->amortizacion_cap,2) }}</td>
              <td class="px-4 py-2 text-right">{{ number_format($d->pago_interes,2) }}</td>
              <td class="px-4 py-2 text-right">{{ number_format($d->monto_pago,2) }}</td>
              <td class="px-4 py-2 text-right">{{ number_format($d->saldo_restante,2) }}</td>
              <td class="px-4 py-2 capitalize">{{ ucfirst($d->estado) }}</td>
              <td class="px-4 py-2">
                <button
                  @click="modalPagoId = {{ $d->id }}"
                  class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700"
                  {{ $d->estado === 'pagado' ? 'disabled opacity-50' : '' }}
                >
                  Pagar
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="px-4 py-2 text-center text-gray-500">
                {{ __('No hay abonos generados') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      {{-- Modales de pago --}}
      @foreach($cuenta->detalles as $d)
      <div
        x-cloak
        x-show="modalPagoId === {{ $d->id }}"
        x-transition.opacity
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
      >
        <div
          class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md"
          @click.away="modalPagoId = null"
        >
          <h3 class="font-semibold text-lg mb-4">
            Abono #{{ $d->numero_pago }} — Marcar como pagado
          </h3>

          <form action="{{ route('detalles.update', $d) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Campos ocultos --}}
            <input type="hidden" name="numero_pago"      value="{{ $d->numero_pago }}">
            <input type="hidden" name="fecha_pago"       value="{{ $d->fecha_pago->toDateString() }}">
            <input type="hidden" name="saldo_inicial"    value="{{ $d->saldo_inicial }}">
            <input type="hidden" name="amortizacion_cap" value="{{ $d->amortizacion_cap }}">
            <input type="hidden" name="pago_interes"     value="{{ $d->pago_interes }}">
            <input type="hidden" name="monto_pago"       value="{{ $d->monto_pago }}">
            <input type="hidden" name="saldo_restante"   value="{{ $d->saldo_restante }}">

            {{-- Estado --}}
            <div class="mb-4">
              <label class="block font-medium mb-1">Estado</label>
              <select name="estado" class="w-full rounded border">
                <option value="pendiente" {{ $d->estado==='pendiente'?'selected':'' }}>Pendiente</option>
                <option value="pagado"    {{ $d->estado==='pagado'?'selected':'' }}>Pagado</option>
                <option value="vencido"   {{ $d->estado==='vencido'?'selected':'' }}>Vencido</option>
              </select>
            </div>

            {{-- Caja de origen --}}
            <div class="mb-4">
              <label class="block font-medium mb-1">Caja origen</label>
              <select name="caja_id" class="w-full rounded border">
                <option value="">-- Selecciona caja --</option>
                @foreach($cajas as $c)
                  <option value="{{ $c->id_caja }}" {{ $d->caja_id === $c->id_caja ? 'selected' : '' }}>
                    {{ $c->nombre }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Comentario --}}
            <div class="mb-4">
              <label class="block font-medium mb-1">Comentario</label>
              <textarea name="comentario" rows="3" class="w-full rounded border">{{ old('comentario', $d->comentario) }}</textarea>
            </div>

            {{-- Botones --}}
            <div class="flex justify-end space-x-2">
              <button type="button" @click="modalPagoId = null" class="px-4 py-2 bg-gray-200 rounded">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Guardar</button>
            </div>
          </form>
        </div>
      </div>
      @endforeach

    </div>
  </div>
</x-app-layout>
