{{-- resources/views/user_data/partials/acceso.blade.php --}}
<div x-data="{ show1: false, show2: false }" class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">

  {{-- Nueva contraseña --}}
  <div class="relative">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      Nueva contraseña
    </label>
    <input
      :type="show1 ? 'text' : 'password'"
      name="pass"
      minlength="8"
      required
      class="mt-1 block w-full pr-10 border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
      oninvalid="this.setCustomValidity('La contraseña debe tener al menos 8 caracteres')"
      oninput="this.setCustomValidity('')"
    />
    <button
      type="button"
      @click="show1 = !show1"
      class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500"
      tabindex="-1"
    >
      <template x-if="!show1">
        <!-- Eye icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
      </template>
      <template x-if="show1">
        <!-- Eye Off icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 
                   9.956 0 012.174-3.248M6.223 6.223A9.955 9.955 0 0112 5c4.477 0 
                   8.268 2.943 9.542 7a10.025 10.025 0 01-4.093 5.364M15 12a3 
                   3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 3l18 18" />
        </svg>
      </template>
    </button>
    @error('pass')
      <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
  </div>

  {{-- Confirmar contraseña --}}
  <div class="relative">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
      Confirmar contraseña
    </label>
    <input
      :type="show2 ? 'text' : 'password'"
      name="pass_confirmation"
      minlength="8"
      required
      class="mt-1 block w-full pr-10 border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
      oninvalid="this.setCustomValidity('Confirma la contraseña correctamente')"
      oninput="this.setCustomValidity('')"
    />
    <button
      type="button"
      @click="show2 = !show2"
      class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500"
      tabindex="-1"
    >
      <template x-if="!show2">
        <!-- Eye icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 
                   9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
      </template>
      <template x-if="show2">
        <!-- Eye Off icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-
                   9.542-7a9.956 9.956 0 012.174-3.248M6.223 6.223A9.955 
                   9.955 0 0112 5c4.477 0 8.268 2.943 
                   9.542 7a10.025 10.025 0 01-4.093 5.364M15 12a3 
                   3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 3l18 18" />
        </svg>
      </template>
    </button>
  </div>

</div>
