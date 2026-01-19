<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Ver Empresa') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 space-y-4">

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Nombre</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $empresa->nombre }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">RFC</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $empresa->rfc }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Dirección</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $empresa->direccion }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Ciudad</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $empresa->ciudad }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Estado</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $empresa->estado }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Código Postal</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $empresa->codigo_postal }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">País</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $empresa->pais }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Teléfono</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $empresa->telefono }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Email</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $empresa->email }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Estatus</h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ $empresa->estatus ? 'Activo' : 'Inactivo' }}
        </p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Fecha de creación</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $empresa->fecha_creacion->format('d/m/Y H:i') }}</p>
      </div>

      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Última modificación</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $empresa->fecha_modificacion->format('d/m/Y H:i') }}</p>
      </div>

      <div class="flex justify-end">
        <a href="{{ route('empresas.index') }}"
           class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded">
          {{ __('Volver') }}
        </a>
      </div>
    </div>
  </div>
</x-app-layout>
