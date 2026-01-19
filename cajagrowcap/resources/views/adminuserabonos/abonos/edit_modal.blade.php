{{-- Solo el contenido del modal (sin layout) --}}
<h3 class="text-lg font-semibold mb-4">
  {{ __('Editar Abono #') }}{{ $abono->id }}
</h3>

<form method="POST" action="{{ route('adminuserabonos.abonos.update', $abono->id) }}" class="space-y-4">
  @csrf
  @method('PUT')

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Abono</label>
      <input type="text" name="tipo_abono"
             value="{{ old('tipo_abono', $abono->tipo_abono) }}"
             class="w-full px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-gray-200">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Vencimiento</label>
      <input type="date" name="fecha_vencimiento"
             value="{{ old('fecha_vencimiento', optional($abono->fecha_vencimiento)->format('Y-m-d')) }}"
             class="w-full px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-gray-200">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"># Pago</label>
      <input type="number" name="num_pago"
             value="{{ old('num_pago', $abono->num_pago) }}"
             class="w-full px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-gray-200">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cantidad</label>
      <input type="number" step="0.01" name="cantidad"
             value="{{ old('cantidad', $abono->cantidad) }}"
             class="w-full px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-gray-200">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Saldo Restante</label>
      <input type="number" step="0.01" name="saldo_restante"
             value="{{ old('saldo_restante', $abono->saldo_restante) }}"
             class="w-full px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-gray-200">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mora Generada</label>
      <input type="number" step="0.01" name="mora_generada"
             value="{{ old('mora_generada', $abono->mora_generada) }}"
             class="w-full px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-gray-200">
    </div>

    <div class="sm:col-span-2">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha</label>
      {{-- Para datetime-local: YYYY-MM-DDTHH:MM --}}
      <input type="datetime-local" name="fecha"
             value="{{ old('fecha', optional($abono->fecha)->format('Y-m-d\TH:i')) }}"
             class="w-full px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-gray-200">
    </div>

    <div class="sm:col-span-2">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
      <select name="status"
              class="w-full px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-gray-200">
        <option value="0" @selected(old('status', $abono->status) == 0)>Pendiente</option>
        <option value="1" @selected(old('status', $abono->status) == 1)>Pagado</option>
        <option value="2" @selected(old('status', $abono->status) == 2)>Vencido</option>
      </select>
    </div>
  </div>

  <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
    <button type="button"
            @click="$root.__x.$data.closeModal()"
            class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
      Cerrar
    </button>
    <button type="submit"
            class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">
      Guardar cambios
    </button>
  </div>
</form>
