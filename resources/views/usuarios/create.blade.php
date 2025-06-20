<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Crear Usuario') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
      @if($errors->any())
        <div class="mb-4 text-red-600">
          <ul class="list-disc pl-5">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('usuarios.store') }}" method="POST">
        @csrf

        <div class="mb-4">
          <label class="block text-gray-700 dark:text-gray-200">Nombre</label>
          <input type="text" name="name"
                 value="{{ old('name') }}"
                 class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700
                        dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500"/>
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 dark:text-gray-200">Email</label>
          <input type="email" name="email"
                 value="{{ old('email') }}"
                 class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700
                        dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500"/>
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 dark:text-gray-200">Contraseña</label>
          <input type="password" name="password"
                 class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700
                        dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500"/>
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 dark:text-gray-200">Confirmar Contraseña</label>
          <input type="password" name="password_confirmation"
                 class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700
                        dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500"/>
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 dark:text-gray-200">Rol</label>
          <select name="rol"
                  class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700
                         dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
            @foreach($roles as $rol)
              <option value="{{ $rol }}"
                {{ old('rol') == $rol ? 'selected' : '' }}>
                {{ ucfirst($rol) }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 dark:text-gray-200">Activo</label>
          <select name="activo"
                  class="mt-1 border rounded px-3 py-2 bg-white dark:bg-gray-700
                         dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
            <option value="1" {{ old('activo','1')=='1' ? 'selected':'' }}>Sí</option>
            <option value="0" {{ old('activo')=='0' ? 'selected':'' }}>No</option>
          </select>
        </div>

        <div class="mb-6">
          <label class="block text-gray-700 dark:text-gray-200">Fecha de creación</label>
          <input type="datetime-local" name="fecha_creacion"
                 value="{{ old('fecha_creacion', now()->format('Y-m-d\TH:i')) }}"
                 class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700
                        dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500"/>
        </div>

        <div class="flex justify-end space-x-2">
          <a href="{{ route('usuarios.index') }}"
             class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded">Cancelar</a>
          <button type="submit"
                  class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded">
            Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
