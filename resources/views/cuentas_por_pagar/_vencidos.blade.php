{{-- Lista compacta de abonos vencidos (se inserta en el colapsable) --}}
<div class="px-4 pb-3">
  @if($vencidos->isEmpty())
    <div class="text-sm text-gray-500">No hay abonos vencidos.</div>
  @else
    <div class="space-y-2">
      @foreach($vencidos as $d)
        <div class="flex items-center justify-between rounded-md border px-3 py-2 text-sm dark:border-gray-800">
          <div class="flex items-center gap-4">
            <span class="font-medium">#{{ $d->numero_pago }}</span>
            <span>{{ optional($d->fecha_pago)->format('d M Y') }}</span>
            <span class="font-semibold">${{ number_format($d->monto_pago,2) }}</span>
            <span class="text-red-600">Vencido</span>
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:border-gray-700"
              @click="$dispatch('cpp-open-pay', {
                tipo:'abono',
                detalleId: {{ $d->id }},
                cuentaId: {{ $cuenta->id_cuentas_por_pagar }},
                montoAbono: {{ (float) $d->monto_pago }}
              })"
            >
              Pagar
            </button>
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-3">
      <button
        type="button"
        class="rounded-md bg-blue-600 px-3 py-2 text-white hover:bg-blue-700 text-sm"
        @click="$dispatch('cpp-open-pay', {
          tipo:'total_vencido',
          detalleId: null,
          cuentaId: {{ $cuenta->id_cuentas_por_pagar }},
          montoAbono: {{ (float) $vencidos->sum('monto_pago') }}
        })"
      >
        Pagar total vencido (${{ number_format($vencidos->sum('monto_pago'),2) }})
      </button>
    </div>
  @endif
</div>
