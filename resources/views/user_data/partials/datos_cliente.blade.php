{{-- resources/views/clientes/partials/datos_cliente.blade.php --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

  {{-- Estado --}}
  <div x-data>
    <label class="block text-sm font-medium text-gray-700">Estado</label>
    <select name="id_estado" x-model="estado"
            @change="
              fetch('/api/municipios?estado='+estado)
                .then(r=>r.json())
                .then(list=>{
                  $refs.muni.innerHTML = '<option value=\"\">— Selecciona —</option>';
                  list.forEach(m=>
                    $refs.muni.insertAdjacentHTML('beforeend',
                      `<option value=\"${m.id}\" ${m.id==@json(old('id_municipio',$userData->id_municipio))?'selected':''}>${m.nombre}</option>`
                    )
                  );
                });
            "
            class="mt-1 block w-full border-gray-300 rounded shadow-sm">
      <option value="">— Selecciona —</option>
      @foreach($estados as $id=>$nombre)
        <option value="{{ $id }}"
          {{ old('id_estado',$userData->id_estado ?? '')==$id?'selected':'' }}>
          {{ $nombre }}
        </option>
      @endforeach
    </select>
    @error('id_estado')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

  {{-- Municipio --}}
  <div>
    <label class="block text-sm font-medium text-gray-700">Municipio</label>
    <select name="id_municipio" x-ref="muni"
            class="mt-1 block w-full border-gray-300 rounded shadow-sm">
      <option value="">— Selecciona —</option>
      @foreach($municipios as $id=>$nombre)
        <option value="{{ $id }}"
          {{ old('id_municipio',$userData->id_municipio ?? '')==$id?'selected':'' }}>
          {{ $nombre }}
        </option>
      @endforeach
    </select>
    @error('id_municipio')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

  {{-- RFC --}}
  <div>
    <label class="block text-sm font-medium text-gray-700">RFC</label>
    <input type="text" name="rfc"
           value="{{ old('rfc',$userData->rfc ?? '') }}"
           class="mt-1 block w-full border-gray-300 rounded shadow-sm"/>
    @error('rfc')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

  {{-- Dirección --}}
  <div>
    <label class="block text-sm font-medium text-gray-700">Dirección</label>
    <input type="text" name="direccion"
           value="{{ old('direccion',$userData->direccion ?? '') }}"
           class="mt-1 block w-full border-gray-300 rounded shadow-sm"/>
    @error('direccion')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

  {{-- Colonia --}}
  <div>
    <label class="block text-sm font-medium text-gray-700">Colonia</label>
    <input type="text" name="colonia"
           value="{{ old('colonia',$userData->colonia ?? '') }}"
           class="mt-1 block w-full border-gray-300 rounded shadow-sm"/>
    @error('colonia')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

  {{-- CP --}}
  <div>
    <label class="block text-sm font-medium text-gray-700">CP</label>
    <input type="text" name="cp"
           value="{{ old('cp',$userData->cp ?? '') }}"
           class="mt-1 block w-full border-gray-300 rounded shadow-sm"/>
    @error('cp')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

  {{-- Banco --}}
  <div>
    <label class="block text-sm font-medium text-gray-700">Banco</label>
    <input type="text" name="banco"
           value="{{ old('banco',$userData->banco ?? '') }}"
           class="mt-1 block w-full border-gray-300 rounded shadow-sm"/>
    @error('banco')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

  {{-- Cuenta --}}
  <div>
    <label class="block text-sm font-medium text-gray-700">Cuenta</label>
    <input type="text" name="cuenta"
           value="{{ old('cuenta',$userData->cuenta ?? '') }}"
           class="mt-1 block w-full border-gray-300 rounded shadow-sm"/>
    @error('cuenta')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

  {{-- NIP --}}
  <div class="md:col-span-2">
    <label class="block text-sm font-medium text-gray-700">NIP</label>
    <textarea name="nip"
              class="mt-1 block w-full border-gray-300 rounded shadow-sm">{{ old('nip',$userData->nip ?? '') }}</textarea>
    @error('nip')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

  {{-- Porcentajes --}}
  <div>
    <label class="block text-sm font-medium text-gray-700">% 1</label>
    <input type="number" step="0.01" name="porcentaje_1"
           value="{{ old('porcentaje_1',$userData->porcentaje_1 ?? '') }}"
           class="mt-1 block w-full border-gray-300 rounded shadow-sm"/>
    @error('porcentaje_1')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

  <div>
    <label class="block text-sm font-medium text-gray-700">% 2</label>
    <input type="number" step="0.01" name="porcentaje_2"
           value="{{ old('porcentaje_2',$userData->porcentaje_2 ?? '') }}"
           class="mt-1 block w-full border-gray-300 rounded shadow-sm"/>
    @error('porcentaje_2')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

  {{-- Fecha de Ingreso --}}
  <div>
    <label class="block text-sm font-medium text-gray-700">Fecha Ingreso</label>
    <input type="date" name="fecha_ingreso"
           value="{{ old('fecha_ingreso',$userData->fecha_ingreso ?? '') }}"
           class="mt-1 block w-full border-gray-300 rounded shadow-sm"/>
    @error('fecha_ingreso')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

</div>
