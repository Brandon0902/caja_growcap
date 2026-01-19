<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Nuevo depósito de emergencia') }}
      </h2>
      <a href="{{ route('depositos.index') }}"
         class="inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-md">
        {{ __('← Volver') }}
      </a>
    </div>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    @if ($errors->any())
      <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-900/40 dark:text-red-200">
        <div class="font-semibold mb-1">{{ __('Hay errores en el formulario:') }}</div>
        <ul class="list-disc ml-5 text-sm">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <form method="POST" action="{{ route('depositos.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        {{-- Cliente --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Cliente') }}</label>
          <select name="id_cliente"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600
                         bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm
                         focus:border-green-500 focus:ring-green-500" required>
            <option value="">{{ __('Selecciona un cliente…') }}</option>
            @foreach($clientes as $c)
              <option value="{{ $c->id }}" @selected(old('id_cliente')==$c->id)>
                {{ $c->nombre }} {{ $c->apellido }} — {{ $c->email }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Monto --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Monto') }}</label>
          <input type="number" name="cantidad" step="0.01" min="0.01" value="{{ old('cantidad') }}"
                 class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm
                        focus:border-green-500 focus:ring-green-500" required>
        </div>

        {{-- Fecha --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Fecha de depósito') }}</label>
          <input type="date" name="fecha_deposito"
                 value="{{ old('fecha_deposito', now()->toDateString()) }}"
                 class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm
                        focus:border-green-500 focus:ring-green-500" required>
        </div>

        {{-- Caja --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Caja a impactar') }}</label>
          <select name="id_caja"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600
                         bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm
                         focus:border-green-500 focus:ring-green-500" required>
            <option value="">{{ __('Selecciona una caja…') }}</option>
            @foreach($cajas as $cx)
              <option value="{{ $cx->id_caja }}" @selected(old('id_caja')==$cx->id_caja)>{{ $cx->nombre }}</option>
            @endforeach
          </select>
          @if(\Illuminate\Support\Facades\Schema::hasColumn('cajas','estado'))
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Solo se listan cajas abiertas.') }}</p>
          @endif
        </div>

        {{-- Nota --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Nota (opcional)') }}</label>
          <input type="text" name="nota" value="{{ old('nota') }}"
                 placeholder="{{ __('Depósito realizado por admin (emergencia)') }}"
                 class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm
                        focus:border-green-500 focus:ring-green-500">
        </div>

        {{-- Comprobante (opcional) --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Comprobante (opcional)') }}</label>
          <input type="file" name="deposito" accept=".jpg,.jpeg,.png,.pdf"
                 class="mt-1 block w-full text-sm text-gray-900 dark:text-gray-100
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-md file:border-0
                        file:text-sm file:font-semibold
                        file:bg-green-50 file:text-green-700
                        hover:file:bg-green-100">
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            {{ __('JPG, PNG o PDF (máx. 4MB).') }}
          </p>
        </div>

        <div class="pt-2 flex justify-end gap-3">
          <a href="{{ route('depositos.index') }}"
             class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-100 rounded-md">
            {{ __('Cancelar') }}
          </a>
          <button type="submit"
                  class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">
            {{ __('Guardar y aprobar') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
