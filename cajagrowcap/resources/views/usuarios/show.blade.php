<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Ver Usuario') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6 space-y-4">
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Nombre</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $usuario->name }}</p>
      </div>
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Email</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $usuario->email }}</p>
      </div>
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Rol</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ ucfirst($usuario->rol) }}</p>
      </div>
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Activo</h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ $usuario->activo ? 'Sí' : 'No' }}
        </p>
      </div>
      <div>
        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Fecha de creación</h3>
        <p class="text-gray-700 dark:text-gray-300">
          {{ $usuario->fecha_creacion->format('Y-m-d H:i:s') }}
        </p>
      </div>
      <div class="flex justify-end">
        <a href="{{ route('usuarios.index') }}"
           class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded">Volver</a>
      </div>
    </div>
  </div>
</x-app-layout>
