{{-- resources/views/adminuserprestamos/edit.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Editar Préstamo #') }}{{ str_pad($prestamo->id, 3, '0', STR_PAD_LEFT) }}
      </h2>
      <a href="{{ route('user_prestamos.show', $prestamo->id_cliente) }}"
         class="inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600
                text-white text-sm font-medium rounded-md shadow-sm">
        {{ __('← Volver') }}
      </a>
    </div>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    @if ($errors->any())
      <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
        <ul class="list-disc list-inside">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('user_prestamos.update', $prestamo) }}" method="POST">
      @csrf
      @method('PUT')

      <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 space-y-6">
        {{-- Estado del préstamo --}}
        <div>
          <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ __('Estado Préstamo') }}
          </label>
          <select id="status" name="status" required
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md
                         bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:ring focus:ring-purple-500">
            @foreach($statusOptions as $key => $label)
              <option value="{{ $key }}"
                {{ old('status', $prestamo->status) == $key ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Nota --}}
        <div>
          <label for="nota" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ __('Nota') }}
          </label>
          <textarea id="nota" name="nota" rows="4"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md
                           bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:ring focus:ring-purple-500"
          >{{ old('nota', $prestamo->nota) }}</textarea>
        </div>

        {{-- Botón Guardar --}}
        <div class="flex justify-end">
          <button type="submit"
                  class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700
                         text-white text-sm font-medium rounded-md shadow-sm focus:outline-none
                         focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            {{ __('Guardar Cambios') }}
          </button>
        </div>
      </div>
    </form>
  </div>
</x-app-layout>
