<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Nuevo Cliente') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4">
    <x-validation-errors class="mb-4"/>
    <div class="bg-white p-6 shadow-sm rounded-lg">
      <form action="{{ route('clientes.store') }}" method="POST">
        @csrf

        <div class="mb-4 grid grid-cols-2 gap-4">
          <div>
            <x-label for="nombre" value="Nombre" />
            <x-input id="nombre" name="nombre" type="text"
                     :value="old('nombre')" required/>
          </div>
          <div>
            <x-label for="apellido" value="Apellido" />
            <x-input id="apellido" name="apellido" type="text"
                     :value="old('apellido')" />
          </div>
        </div>

        <div class="mb-4">
          <x-label for="email" value="Email" />
          <x-input id="email" name="email" type="email"
                   :value="old('email')" />
        </div>

        <div class="mb-4">
          <x-label for="telefono" value="Teléfono" />
          <x-input id="telefono" name="telefono" type="text"
                   :value="old('telefono')" />
        </div>

        <div class="mb-4 grid grid-cols-2 gap-4">
          <div>
            <x-label for="user" value="Usuario (login)" />
            <x-input id="user" name="user" type="text"
                     :value="old('user')" />
          </div>
          <div>
            <x-label for="pass" value="Contraseña" />
            <x-input id="pass" name="pass" type="password" />
          </div>
        </div>

        <div class="mb-4">
          <x-label for="tipo" value="Tipo" />
          <x-input id="tipo" name="tipo" type="text"
                   :value="old('tipo','Cliente')" />
        </div>

        <div class="mb-4">
          <x-label for="fecha" value="Fecha de Registro" />
          <x-input id="fecha" name="fecha" type="date"
                   :value="old('fecha')" />
        </div>

        <div class="flex justify-end">
          <a href="{{ route('clientes.index') }}"
             class="px-4 py-2 mr-3 bg-gray-200 rounded">Cancelar</a>
          <x-button type="submit">Guardar</x-button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
