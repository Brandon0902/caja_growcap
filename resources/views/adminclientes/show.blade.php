<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Ver Cliente') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4">
    <div class="bg-white p-6 shadow-sm rounded-lg space-y-4">

      <div>
        <h3 class="font-semibold">Código Cliente</h3>
        <p>{{ $cliente->codigo_cliente }}</p>
      </div>

      <div>
        <h3 class="font-semibold">Nombre</h3>
        <p>{{ $cliente->nombre }} {{ $cliente->apellido }}</p>
      </div>

      <div>
        <h3 class="font-semibold">Email</h3>
        <p>{{ $cliente->email }}</p>
      </div>

      <div>
        <h3 class="font-semibold">Teléfono</h3>
        <p>{{ $cliente->telefono }}</p>
      </div>

      <div>
        <h3 class="font-semibold">Usuario</h3>
        <p>{{ $cliente->user }}</p>
      </div>

      <div>
        <h3 class="font-semibold">Tipo</h3>
        <p>{{ $cliente->tipo }}</p>
      </div>

      <div>
        <h3 class="font-semibold">Fecha Registro</h3>
        <p>{{ optional($cliente->fecha)->format('d/m/Y H:i') }}</p>
      </div>

      <div>
        <h3 class="font-semibold">Última Edición</h3>
        <p>{{ optional($cliente->fecha_edit)->format('d/m/Y H:i') }}</p>
      </div>

      <div>
        <h3 class="font-semibold">Último Acceso</h3>
        <p>{{ optional($cliente->ultimo_acceso)->format('d/m/Y H:i') }}</p>
      </div>

      <div>
        <h3 class="font-semibold">Status</h3>
        <p>{{ $cliente->status ? 'Activo' : 'Inactivo' }}</p>
      </div>

      <div class="flex justify-end">
        <a href="{{ route('clientes.index') }}"
           class="px-4 py-2 bg-gray-200 rounded">Volver</a>
      </div>
    </div>
  </div>
</x-app-layout>
