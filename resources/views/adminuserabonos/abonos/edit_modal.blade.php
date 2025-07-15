<div class="modal-header">
  <h5 class="modal-title">
    {{ __('Editar Abono #:id', ['id' => $abono->id]) }}
  </h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form action="{{ route('adminuserabonos.abonos.update', $abono->id) }}"
      method="POST" class="space-y-4 p-4">
  @csrf @method('PUT')

  <div>
    <label class="block text-sm font-medium">{{ __('Tipo de Abono') }}</label>
    <input type="text" name="tipo_abono"
           value="{{ old('tipo_abono',$abono->tipo_abono) }}"
           class="mt-1 block w-full border-gray-300 rounded-md"/>
  </div>

  <div>
    <label class="block text-sm font-medium">{{ __('Fecha Vencimiento') }}</label>
    <input type="date" name="fecha_vencimiento"
           value="{{ old('fecha_vencimiento',$abono->fecha_vencimiento?->format('Y-m-d')) }}"
           class="mt-1 block w-full border-gray-300 rounded-md"/>
  </div>

  <div>
    <label class="block text-sm font-medium">{{ __('# Pago') }}</label>
    <input type="number" name="num_pago"
           value="{{ old('num_pago',$abono->num_pago) }}"
           class="mt-1 block w-full border-gray-300 rounded-md"/>
  </div>

  <div>
    <label class="block text-sm font-medium">{{ __('Cantidad') }}</label>
    <input type="number" step="0.01" name="cantidad"
           value="{{ old('cantidad',$abono->cantidad) }}"
           class="mt-1 block w-full border-gray-300 rounded-md"/>
  </div>

  <div>
    <label class="block text-sm font-medium">{{ __('Saldo Restante') }}</label>
    <input type="number" step="0.01" name="saldo_restante"
           value="{{ old('saldo_restante',$abono->saldo_restante) }}"
           class="mt-1 block w-full border-gray-300 rounded-md"/>
  </div>

  <div>
    <label class="block text-sm font-medium">{{ __('Mora Generada') }}</label>
    <input type="number" step="0.01" name="mora_generada"
           value="{{ old('mora_generada',$abono->mora_generada) }}"
           class="mt-1 block w-full border-gray-300 rounded-md"/>
  </div>

  <div>
    <label class="block text-sm font-medium">{{ __('Fecha') }}</label>
    <input type="datetime-local" name="fecha"
           value="{{ old('fecha',$abono->fecha?->format('Y-m-d\TH:i')) }}"
           class="mt-1 block w-full border-gray-300 rounded-md"/>
  </div>

  <div>
    <label class="block text-sm font-medium">{{ __('Status') }}</label>
    <select name="status" class="mt-1 block w-full border-gray-300 rounded-md">
      <option value="0" @selected(old('status',$abono->status)==0)>
        {{ __('Pendiente') }}
      </option>
      <option value="1" @selected(old('status',$abono->status)==1)>
        {{ __('Pagado') }}
      </option>
      <option value="2" @selected(old('status',$abono->status)==2)>
        {{ __('Vencido') }}
      </option>
    </select>
  </div>

  <div class="modal-footer flex justify-end space-x-2">
    <button type="button"
            class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-400"
            data-bs-dismiss="modal">
      {{ __('Cerrar') }}
    </button>
    <button type="submit"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
      {{ __('Guardar cambios') }}
    </button>
  </div>
</form>
