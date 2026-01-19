<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Nueva Empresa') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <x-validation-errors class="mb-4"/>

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
      <form action="{{ route('empresas.store') }}" method="POST">
        @csrf

        {{-- Nombre --}}
        <div class="mb-4">
          <x-label for="nombre" value="Nombre" />
          <x-input id="nombre"
                   name="nombre"
                   type="text"
                   maxlength="255"
                   :value="old('nombre')"
                   required
                   autofocus />
        </div>

        {{-- RFC --}}
        <div class="mb-4">
          <x-label for="rfc" value="RFC" />
          <x-input id="rfc"
                   name="rfc"
                   type="text"
                   maxlength="20"
                   :value="old('rfc')" />
        </div>

        {{-- Dirección --}}
        <div class="mb-4">
          <x-label for="direccion" value="Dirección" />
          <x-input id="direccion"
                   name="direccion"
                   type="text"
                   maxlength="500"
                   :value="old('direccion')" />
        </div>

        {{-- Ciudad --}}
        <div class="mb-4">
          <x-label for="ciudad" value="Ciudad" />
          <x-input id="ciudad"
                   name="ciudad"
                   type="text"
                   maxlength="100"
                   :value="old('ciudad')" />
        </div>

        {{-- Estado --}}
        <div class="mb-4">
          <x-label for="estado" value="Estado" />
          <x-input id="estado"
                   name="estado"
                   type="text"
                   maxlength="100"
                   :value="old('estado')" />
        </div>

        {{-- Código Postal --}}
        <div class="mb-4">
          <x-label for="codigo_postal" value="Código Postal" />
          <x-input id="codigo_postal"
                   name="codigo_postal"
                   type="text"
                   maxlength="20"
                   :value="old('codigo_postal')" />
        </div>

        {{-- País --}}
        <div class="mb-4">
          <x-label for="pais" value="País" />
          <x-input id="pais"
                   name="pais"
                   type="text"
                   maxlength="100"
                   :value="old('pais', 'México')"
                   required />
        </div>

        {{-- Teléfono --}}
        <div class="mb-4">
          <x-label for="telefono" value="Teléfono" />
          <x-input id="telefono"
                   name="telefono"
                   type="text"
                   maxlength="50"
                   :value="old('telefono')" />
        </div>

        {{-- Email --}}
        <div class="mb-4">
          <x-label for="email" value="Email" />
          <x-input id="email"
                   name="email"
                   type="email"
                   maxlength="150"
                   :value="old('email')" />
        </div>

        {{-- Estatus --}}
        <div class="mb-6">
          <x-label for="estatus" value="Estatus" />
          <select id="estatus"
                  name="estatus"
                  class="mt-1 block w-full border rounded px-3 py-2
                         bg-white dark:bg-gray-700 dark:text-gray-200
                         focus:outline-none focus:ring-2 focus:ring-purple-500">
            <option value="1" {{ old('estatus','1')=='1'?'selected':'' }}>Activo</option>
            <option value="0" {{ old('estatus')=='0'?'selected':'' }}>Inactivo</option>
          </select>
        </div>

        <div class="flex justify-end space-x-3">
          <a href="{{ route('empresas.index') }}"
             class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md
                    text-gray-700 bg-white hover:bg-gray-100 focus:outline-none
                    focus:ring-2 focus:ring-offset-2 focus:ring-gray-300
                    dark:border-gray-600 dark:text-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-500">
            {{ __('Cancelar') }}
          </a>

          <x-button type="submit"
                    class="bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600">
            {{ __('Guardar') }}
          </x-button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
