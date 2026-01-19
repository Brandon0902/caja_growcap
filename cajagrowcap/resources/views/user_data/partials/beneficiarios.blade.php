{{-- resources/views/user_data/partials/beneficiarios.blade.php --}}

{{-- Beneficiario 1: nombre, teléfono y porcentaje --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
  {{-- Nombre Beneficiario 1 --}}
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      Beneficiario 1
    </label>
    <input
      type="text"
      name="beneficiario"
      value="{{ old('beneficiario', $userData->beneficiario ?? '') }}"
      required
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    />
    @error('beneficiario')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>

  {{-- Teléfono Beneficiario 1 --}}
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      Teléfono Beneficiario 1
    </label>
    <input
      type="tel"
      name="beneficiario_telefono"
      value="{{ old('beneficiario_telefono', $userData->beneficiario_telefono ?? '') }}"
      required
      pattern="[0-9]{7,15}"
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    />
    @error('beneficiario_telefono')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>

  {{-- % Beneficiario 1 --}}
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      % Beneficiario 1
    </label>
    <input
      id="porcentaje_1"
      type="number"
      name="porcentaje_1"
      min="0"
      max="100"
      step="0.01"
      value="{{ old('porcentaje_1', $userData->porcentaje_1 ?? '') }}"
      required
      oninput="
        const p1 = parseFloat(this.value) || 0;
        const p2 = parseFloat(document.getElementById('porcentaje_2').value) || 0;
        const valid = (p1 + p2).toFixed(2) == 100;
        this.setCustomValidity(valid ? '' : 'La suma de porcentajes debe ser 100%');
        document.getElementById('porcentaje_2').setCustomValidity(valid ? '' : 'La suma de porcentajes debe ser 100%');
      "
      onchange="this.reportValidity(); document.getElementById('porcentaje_2').reportValidity()"
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    />
    @error('porcentaje_1')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>
</div>

{{-- Beneficiario 2: nombre, teléfono y porcentaje --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
  {{-- Nombre Beneficiario 2 --}}
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      Beneficiario 2
    </label>
    <input
      type="text"
      name="beneficiario_02"
      value="{{ old('beneficiario_02', $userData->beneficiario_02 ?? '') }}"
      required
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    />
    @error('beneficiario_02')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>

  {{-- Teléfono Beneficiario 2 --}}
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      Teléfono Beneficiario 2
    </label>
    <input
      type="tel"
      name="beneficiario_telefono_02"
      value="{{ old('beneficiario_telefono_02', $userData->beneficiario_telefono_02 ?? '') }}"
      required
      pattern="[0-9]{7,15}"
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    />
    @error('beneficiario_telefono_02')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>

  {{-- % Beneficiario 2 --}}
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      % Beneficiario 2
    </label>
    <input
      id="porcentaje_2"
      type="number"
      name="porcentaje_2"
      min="0"
      max="100"
      step="0.01"
      value="{{ old('porcentaje_2', $userData->porcentaje_2 ?? '') }}"
      required
      oninput="
        const p2 = parseFloat(this.value) || 0;
        const p1 = parseFloat(document.getElementById('porcentaje_1').value) || 0;
        const valid = (p1 + p2).toFixed(2) == 100;
        this.setCustomValidity(valid ? '' : 'La suma de porcentajes debe ser 100%');
        document.getElementById('porcentaje_1').setCustomValidity(valid ? '' : 'La suma de porcentajes debe ser 100%');
      "
      onchange="this.reportValidity(); document.getElementById('porcentaje_1').reportValidity()"
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    />
    @error('porcentaje_2')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>
</div>
