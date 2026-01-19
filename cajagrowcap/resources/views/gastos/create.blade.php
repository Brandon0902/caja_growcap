<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Nueva transacción entre cajas') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <x-validation-errors class="mb-4" />

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <form action="{{ route('gastos.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- TRANSACCIÓN (tipo) --}}
        <div class="mb-4">
          <x-label for="tipo" value="Tipo de transacción" />
          <x-input id="tipo" name="tipo" type="text" :value="old('tipo')" required autofocus />
        </div>

        {{-- CAJA ORIGEN --}}
        <div class="mb-4">
          <x-label for="caja_id" value="Caja origen" />
          <select id="caja_id" name="caja_id"
                  class="mt-1 block w-full border rounded px-3 py-2
                         bg-white dark:bg-gray-700 dark:text-gray-200
                         focus:outline-none focus:ring-2 focus:ring-purple-500">
            <option value="">{{ __('— Seleccionar caja —') }}</option>
            @foreach($cajas as $c)
              <option value="{{ $c->id_caja }}" {{ old('caja_id') == $c->id_caja ? 'selected' : '' }}>
                {{ $c->nombre }} ({{ optional($c->sucursal)->nombre }})
              </option>
            @endforeach
          </select>
          @error('caja_id')<p class="text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- CAJA DESTINO (opcional) --}}
        <div class="mb-4">
          <x-label for="destino_caja_id" value="Caja destino (opcional)" />
          <select id="destino_caja_id" name="destino_caja_id"
                  class="mt-1 block w-full border rounded px-3 py-2
                         bg-white dark:bg-gray-700 dark:text-gray-200
                         focus:outline-none focus:ring-2 focus:ring-purple-500">
            <option value="">{{ __('— Ninguna —') }}</option>
            @foreach($cajas as $c)
              <option value="{{ $c->id_caja }}" {{ old('destino_caja_id') == $c->id_caja ? 'selected' : '' }}>
                {{ $c->nombre }} ({{ optional($c->sucursal)->nombre }})
              </option>
            @endforeach
          </select>
          @error('destino_caja_id')<p class="text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- MONTO --}}
        <div class="mb-4">
          <x-label for="cantidad" value="Monto" />
          <x-input id="cantidad" name="cantidad" type="number" step="0.01" :value="old('cantidad')" required />
        </div>

        {{-- CONCEPTO --}}
        <div class="mb-4">
          <x-label for="concepto" value="Concepto" />
          <textarea id="concepto" name="concepto" rows="3"
                    class="mt-1 block w-full border rounded px-3 py-2
                           bg-white dark:bg-gray-700 dark:text-gray-200
                           focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('concepto') }}</textarea>
        </div>

        {{-- COMPROBANTE (opcional) --}}
        <div class="mb-6">
          <x-label for="comprobante" value="Comprobante (imagen o PDF, opcional)" />
          <input id="comprobante" name="comprobante" type="file" accept=".jpg,.jpeg,.png,.pdf"
                 class="mt-1 block w-full border rounded px-3 py-2
                        bg-white dark:bg-gray-700 dark:text-gray-200
                        focus:outline-none focus:ring-2 focus:ring-purple-500" />
          <p class="text-xs text-gray-500 mt-1">Máx. 4 MB. Formatos: JPG/PNG/PDF.</p>
          @error('comprobante')<p class="text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- BOTONES --}}
        <div class="flex justify-end space-x-3">
          <a href="{{ route('gastos.index') }}"
             class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md
                    text-gray-700 bg-white hover:bg-gray-100 focus:outline-none
                    focus:ring-2 focus:ring-offset-2 focus:ring-gray-300
                    dark:border-gray-600 dark:text-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-500">
            {{ __('Cancelar') }}
          </a>

          <x-button type="submit">
            {{ __('Guardar transacción') }}
          </x-button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
