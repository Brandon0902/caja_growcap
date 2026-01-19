{{-- resources/views/user_data/partials/banco.blade.php --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">

  {{-- Banco --}}
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      Banco
    </label>
    <select
      name="banco"
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    >
      <option value="">— Selecciona banco —</option>
      @php
        $banks = [
          'BBVA',
          'Banamex',
          'Santander',
          'Banorte',
          'HSBC',
          'Scotiabank',
          'Inbursa',
          'Citibanamex',
          'Banco Azteca',
          'Banco del Bajío',
          'BanCoppel',
          'Banca Mifel',
          'Banco Compartamos',
        ];
      @endphp
      @foreach($banks as $bank)
        <option
          value="{{ $bank }}"
          {{ old('banco', $userData->banco ?? '') === $bank ? 'selected' : '' }}
        >
          {{ $bank }}
        </option>
      @endforeach
    </select>
    @error('banco')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>

  {{-- Cuenta (clave limitada a 16 caracteres) --}}
  <div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      Cuenta (máx. 16 dígitos)
    </label>
    <input
      type="text"
      name="cuenta"
      maxlength="16"
      value="{{ old('cuenta', $userData->cuenta ?? '') }}"
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    />
    @error('cuenta')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>

</div>
