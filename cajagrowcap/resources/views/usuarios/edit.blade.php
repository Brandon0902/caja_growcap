<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Editar Usuario') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-4xl px-4 sm:px-6 lg:px-8"
       x-data='{
         selAllSuc: false,
         selAllCaj: false,
         toggleAllSuc() {
           const checks = document.querySelectorAll("input[name=\"sucursales[]\"]");
           checks.forEach(ch => ch.checked = this.selAllSuc);
         },
         toggleAllCaj() {
           const checks = document.querySelectorAll("input[name=\"cajas[]\"]");
           checks.forEach(ch => ch.checked = this.selAllCaj);
         }
       }'>

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

      <form action="{{ route('usuarios.update', $usuario) }}" method="POST">
        @csrf @method('PUT')

        <div class="mb-4">
          <label class="block text-gray-700 dark:text-gray-200">Nombre</label>
          <input type="text" name="name"
                 value="{{ old('name', $usuario->name) }}"
                 class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700
                        dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500"/>
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 dark:text-gray-200">Email</label>
          <input type="email" name="email"
                 value="{{ old('email', $usuario->email) }}"
                 class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700
                        dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500"/>
        </div>

        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-700 dark:text-gray-200">Nueva Contraseña (opcional)</label>
            <input type="password" name="password"
                   class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700
                          dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500"/>
          </div>
          <div>
            <label class="block text-gray-700 dark:text-gray-200">Confirmar</label>
            <input type="password" name="password_confirmation"
                   class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700
                          dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500"/>
          </div>
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 dark:text-gray-200">Rol</label>
          <select name="rol"
                  class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700
                         dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
            @foreach($roles as $rol)
              <option value="{{ $rol }}"
                {{ old('rol', $usuario->rol) == $rol ? 'selected' : '' }}>
                {{ ucfirst($rol) }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-700 dark:text-gray-200">Activo</label>
            <select name="activo"
                    class="mt-1 border rounded px-3 py-2 bg-white dark:bg-gray-700
                           dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
              <option value="1" {{ old('activo', $usuario->activo)==1 ? 'selected':'' }}>Sí</option>
              <option value="0" {{ old('activo', $usuario->activo)==0 ? 'selected':'' }}>No</option>
            </select>
          </div>
          <div>
            <label class="block text-gray-700 dark:text-gray-200">Fecha de creación</label>
            <input type="datetime-local" name="fecha_creacion"
                   value="{{ old('fecha_creacion', $usuario->fecha_creacion?->format('Y-m-d\TH:i')) }}"
                   class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700
                          dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-500"/>
          </div>
        </div>

        {{-- Sucursal principal (opcional) --}}
        <div class="mb-6">
          <label class="block text-gray-700 dark:text-gray-200">Sucursal principal (opcional)</label>
          <select name="sucursal_principal"
                  class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700 dark:text-gray-200
                         focus:outline-none focus:ring-2 focus:ring-purple-500">
            <option value="">-- Ninguna --</option>
            @foreach($sucursales as $s)
              <option value="{{ $s->id_sucursal }}"
                {{ old('sucursal_principal', $usuario->id_sucursal) == $s->id_sucursal ? 'selected' : '' }}>
                {{ $s->nombre }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Asignar Sucursales (múltiple) --}}
        <div class="mb-6">
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">Sucursales</h3>
            <label class="text-sm flex items-center gap-2">
              <input type="checkbox" x-model="selAllSuc" @change="toggleAllSuc()" class="rounded border-gray-300">
              <span class="text-gray-700 dark:text-gray-300">Seleccionar todas</span>
            </label>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($sucursales as $s)
              <label class="flex items-center gap-2 px-3 py-2 rounded border
                            bg-white dark:bg-gray-700 dark:text-gray-200">
                <input type="checkbox" name="sucursales[]"
                       value="{{ $s->id_sucursal }}"
                       {{ in_array($s->id_sucursal, old('sucursales', $sucursalesChecked)) ? 'checked' : '' }}
                       class="rounded border-gray-300">
                <span>{{ $s->nombre }}</span>
              </label>
            @endforeach
          </div>
          @error('sucursales.*')
            <p class="mt-1 text-red-600 text-sm">{{ $message }}</p>
          @enderror
        </div>

        {{-- Asignar Cajas (múltiple) --}}
        <div class="mb-8">
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">Cajas</h3>
            <label class="text-sm flex items-center gap-2">
              <input type="checkbox" x-model="selAllCaj" @change="toggleAllCaj()" class="rounded border-gray-300">
              <span class="text-gray-700 dark:text-gray-300">Seleccionar todas</span>
            </label>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($cajas as $c)
              <label class="flex items-center gap-2 px-3 py-2 rounded border
                            bg-white dark:bg-gray-700 dark:text-gray-200">
                <input type="checkbox" name="cajas[]"
                       value="{{ $c->id_caja }}"
                       {{ in_array($c->id_caja, old('cajas', $cajasChecked)) ? 'checked' : '' }}
                       class="rounded border-gray-300">
                <span>{{ $c->nombre }}</span>
              </label>
            @endforeach
          </div>
          @error('cajas.*')
            <p class="mt-1 text-red-600 text-sm">{{ $message }}</p>
          @enderror
        </div>

        <div class="flex justify-end gap-2">
          <a href="{{ route('usuarios.index') }}"
             class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded">Cancelar</a>
          <button type="submit"
                  class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded">
            Actualizar
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
