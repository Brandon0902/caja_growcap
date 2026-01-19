{{-- resources/views/user_data/partials/seguridad.blade.php --}}
<div class="grid grid-cols-1 mb-6">
  <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
    NIP
  </label>
  <input
    type="text"
    name="nip"
    value="{{ old('nip', $userData->nip ?? '') }}"
    pattern="\d{4}"
    maxlength="4"
    minlength="4"
    inputmode="numeric"
    required
    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
    oninvalid="this.setCustomValidity('El NIP debe tener exactamente 4 dígitos numéricos')"
    oninput="this.setCustomValidity('')"
  />
  @error('nip')
    <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
  @enderror
</div>
