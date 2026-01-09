<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Ver Cliente') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <h3 class="font-semibold text-gray-800 dark:text-gray-200">Código</h3>
          <p class="text-gray-700 dark:text-gray-300">{{ $cliente->codigo_cliente }}</p>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800 dark:text-gray-200">Sucursal</h3>
          <p class="text-gray-700 dark:text-gray-300">{{ $cliente->sucursal->nombre ?? '—' }}</p>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800 dark:text-gray-200">Nombre</h3>
          <p class="text-gray-700 dark:text-gray-300">{{ $cliente->nombre }} {{ $cliente->apellido }}</p>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800 dark:text-gray-200">Email</h3>
          <p class="text-gray-700 dark:text-gray-300">{{ $cliente->email }}</p>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800 dark:text-gray-200">Teléfono</h3>
          <p class="text-gray-700 dark:text-gray-300">{{ $cliente->telefono }}</p>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800 dark:text-gray-200">Tipo</h3>
          <p class="text-gray-700 dark:text-gray-300">{{ $cliente->tipo }}</p>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800 dark:text-gray-200">Status</h3>
          <p class="text-gray-700 dark:text-gray-300">{{ $cliente->status ? 'Activo' : 'Inactivo' }}</p>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800 dark:text-gray-200">Fecha de Registro</h3>
          <p class="text-gray-700 dark:text-gray-300">{{ optional($cliente->fecha)->format('d/m/Y') }}</p>
        </div>
        <div>
          <h3 class="font-semibold text-gray-800 dark:text-gray-200">Última edición</h3>
          <p class="text-gray-700 dark:text-gray-300">{{ optional($cliente->fecha_edit)->format('d/m/Y H:i') }}</p>
        </div>
      </div>

      <div class="pt-4">
        <a href="{{ route('clientes.index') }}" class="text-blue-600 hover:underline">Volver</a>
      </div>
    </div>
  </div>
</x-app-layout>
