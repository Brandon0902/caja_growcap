{{-- resources/views/user_data/partials/general.blade.php --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">

  {{-- Estado --}}
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      Estado
    </label>
    <select
      name="id_estado"
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    >
      <option value="">— Selecciona —</option>
      @foreach($estados as $id => $nombre)
        <option value="{{ $id }}"
          {{ old('id_estado', $userData->id_estado) == $id ? 'selected' : '' }}>
          {{ $nombre }}
        </option>
      @endforeach
    </select>
    @error('id_estado')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>

  {{-- Municipio --}}
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      Municipio
    </label>
    <select
      name="id_municipio"
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    >
      <option value="">— Selecciona —</option>
      @foreach($municipios as $id => $nombre)
        <option value="{{ $id }}"
          {{ old('id_municipio', $userData->id_municipio) == $id ? 'selected' : '' }}>
          {{ $nombre }}
        </option>
      @endforeach
    </select>
    @error('id_municipio')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>

</div>

{{-- RFC --}}
<div class="sm:col-span-2 mb-6">
  <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
    RFC
  </label>
  <input
    type="text"
    name="rfc"
    value="{{ old('rfc', $userData->rfc ?? '') }}"
    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
  />
  @error('rfc')
    <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
  @enderror
</div>

{{-- Dirección, Colonia, CP --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      Dirección
    </label>
    <input
      type="text"
      name="direccion"
      value="{{ old('direccion', $userData->direccion ?? '') }}"
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    />
    @error('direccion')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>

  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      Colonia
    </label>
    <input
      type="text"
      name="colonia"
      value="{{ old('colonia', $userData->colonia ?? '') }}"
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    />
    @error('colonia')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>

  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      CP
    </label>
    <input
      type="text"
      name="cp"
      value="{{ old('cp', $userData->cp ?? '') }}"
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    />
    @error('cp')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>
</div>
