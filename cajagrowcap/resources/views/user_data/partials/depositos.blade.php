{{-- resources/views/clientes/partials/depositos.blade.php --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
  <div>
    <label class="block text-sm font-medium text-gray-700">% 1</label>
    <input type="number" step="0.01" name="porcentaje_1"
           value="{{ old('porcentaje_1',$userData->porcentaje_1??'') }}"
           class="mt-1 block w-full border-gray-300 rounded"/>
  </div>
  <div>
    <label class="block text-sm font-medium text-gray-700">% 2</label>
    <input type="number" step="0.01" name="porcentaje_2"
           value="{{ old('porcentaje_2',$userData->porcentaje_2??'') }}"
           class="mt-1 block w-full border-gray-300 rounded"/>
  </div>
  <div class="sm:col-span-2">
    <label class="block text-sm font-medium text-gray-700">Fecha Ingreso</label>
    <input type="date" name="fecha_ingreso"
           value="{{ old('fecha_ingreso',$userData->fecha_ingreso??'') }}"
           class="mt-1 block w-full border-gray-300 rounded"/>
  </div>
</div>
