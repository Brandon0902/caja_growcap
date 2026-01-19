<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Crear Usuario') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-4xl px-4 sm:px-6 lg:px-8"
       x-data='{
         roles: @json($roles),
         newRole: "",
         showModal: false,
         selAllSuc: false,
         selAllCaj: false,
         toggleAllSuc() {
           const checks = document.querySelectorAll("input[name=\"sucursales[]\"]");
           checks.forEach(ch => ch.checked = this.selAllSuc);
         },
         toggleAllCaj() {
           const checks = document.querySelectorAll("input[name=\"cajas[]\"]");
           checks.forEach(ch => ch.checked = this.selAllCaj);
         },
         addRole() {
           if (! this.newRole.trim()) return;
           this.roles.push(this.newRole.trim());
           this.$refs.roleSelect.value = this.newRole.trim();
           this.newRole = "";
           this.showModal = false;
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

      <form action="{{ route('usuarios.store') }}" method="POST">
        @csrf

        {{-- Nombre --}}
        <div class="mb-4">
          <label class="block text-gray-700 dark:text-gray-200">Nombre</label>
          <input type="text" name="name" value="{{ old('name') }}"
                 class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700 dark:text-gray-200
                        focus:outline-none focus:ring-2 focus:ring-purple-500"/>
        </div>

        {{-- Email --}}
        <div class="mb-4">
          <label class="block text-gray-700 dark:text-gray-200">Email</label>
          <input type="email" name="email" value="{{ old('email') }}"
                 class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700 dark:text-gray-200
                        focus:outline-none focus:ring-2 focus:ring-purple-500"/>
        </div>

        {{-- Password --}}
        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-700 dark:text-gray-200">Contraseña</label>
            <input type="password" name="password"
                   class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700 dark:text-gray-200
                          focus:outline-none focus:ring-2 focus:ring-purple-500"/>
          </div>
          <div>
            <label class="block text-gray-700 dark:text-gray-200">Confirmar</label>
            <input type="password" name="password_confirmation"
                   class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700 dark:text-gray-200
                          focus:outline-none focus:ring-2 focus:ring-purple-500"/>
          </div>
        </div>

        {{-- Rol + botón modal --}}
        <div class="mb-6">
          <label class="block text-gray-700 dark:text-gray-200">Rol</label>
          <div class="flex gap-2">
            <select x-ref="roleSelect" name="rol"
                    class="flex-1 mt-1 border rounded px-3 py-2 bg-white dark:bg-gray-700 dark:text-gray-200
                           focus:outline-none focus:ring-2 focus:ring-purple-500">
              <option value="" disabled selected>-- Selecciona un rol --</option>
              <template x-for="r in roles" :key="r">
                <option :value="r" x-text="r"></option>
              </template>
            </select>
            <button type="button"
                    @click="showModal = true"
                    class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded">
              + Rol
            </button>
          </div>
          @error('rol')
            <p class="mt-1 text-red-600 text-sm">{{ $message }}</p>
          @enderror
        </div>

        {{-- Activo + Fecha --}}
        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-700 dark:text-gray-200">Activo</label>
            <select name="activo"
                    class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700 dark:text-gray-200
                           focus:outline-none focus:ring-2 focus:ring-purple-500">
              <option value="1" {{ old('activo','1')=='1'?'selected':'' }}>Sí</option>
              <option value="0" {{ old('activo')=='0'?'selected':'' }}>No</option>
            </select>
          </div>
          <div>
            <label class="block text-gray-700 dark:text-gray-200">Fecha de creación</label>
            <input type="datetime-local" name="fecha_creacion"
                   value="{{ old('fecha_creacion', now()->format('Y-m-d\TH:i')) }}"
                   class="mt-1 w-full border rounded px-3 py-2 bg-white dark:bg-gray-700 dark:text-gray-200
                          focus:outline-none focus:ring-2 focus:ring-purple-500"/>
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
              <option value="{{ $s->id_sucursal }}">{{ $s->nombre }}</option>
            @endforeach
          </select>
        </div>

        {{-- Asignar Sucursales (múltiple) --}}
        <div class="mb-6">
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">Sucursales</h3>
            <label class="text-sm flex items-center gap-2">
              <input type="checkbox" x-model="selAllSuc" @change="toggleAllSuc()"
                     class="rounded border-gray-300">
              <span class="text-gray-700 dark:text-gray-300">Seleccionar todas</span>
            </label>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($sucursales as $s)
              <label class="flex items-center gap-2 px-3 py-2 rounded border
                            bg-white dark:bg-gray-700 dark:text-gray-200">
                <input type="checkbox" name="sucursales[]"
                       value="{{ $s->id_sucursal }}"
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
              <input type="checkbox" x-model="selAllCaj" @change="toggleAllCaj()"
                     class="rounded border-gray-300">
              <span class="text-gray-700 dark:text-gray-300">Seleccionar todas</span>
            </label>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($cajas as $c)
              <label class="flex items-center gap-2 px-3 py-2 rounded border
                            bg-white dark:bg-gray-700 dark:text-gray-200">
                <input type="checkbox" name="cajas[]"
                       value="{{ $c->id_caja }}"
                       class="rounded border-gray-300">
                <span>{{ $c->nombre }}</span>
              </label>
            @endforeach
          </div>
          @error('cajas.*')
            <p class="mt-1 text-red-600 text-sm">{{ $message }}</p>
          @enderror
        </div>

        {{-- Botones --}}
        <div class="flex justify-end gap-2">
          <a href="{{ route('usuarios.index') }}"
             class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded">Cancelar</a>
          <button type="submit"
                  class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded">
            Guardar
          </button>
        </div>
      </form>
    </div>

    {{-- Modal para nuevo rol --}}
    <div x-show="showModal" x-cloak
         class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
      <div @click.away="showModal = false"
           class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-sm">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
          {{ __('Nuevo Rol') }}
        </h3>
        <input type="text"
               x-model="newRole"
               placeholder="Nombre del rol"
               class="w-full px-3 py-2 mb-4 border rounded bg-white dark:bg-gray-700 dark:text-gray-200
                      focus:outline-none focus:ring-2 focus:ring-purple-500"/>
        <div class="flex justify-end gap-2">
          <button @click="showModal = false"
                  class="px-4 py-2 bg-gray-200 rounded">Cancelar</button>
          <button @click="addRole()"
                  class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded">
            Agregar
          </button>
        </div>
      </div>
    </div>
    {{-- /modal --}}
  </div>
</x-app-layout>
