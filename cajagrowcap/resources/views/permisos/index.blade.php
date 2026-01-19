{{-- resources/views/permisos/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
        {{ __('Permisos por Rol') }}
      </h2>

      <div class="flex items-center gap-2">
        <form method="POST" action="{{ route('admin.permisos.cacheReset') }}">
          @csrf
          <button class="px-3 py-2 text-sm rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
            Reiniciar caché
          </button>
        </form>

        <form method="POST" action="{{ route('admin.permisos.pruneAndNormalize') }}">
          @csrf
          <button class="px-3 py-2 text-sm rounded-md bg-rose-600 text-white hover:bg-rose-700"
                  title="Podar/normalizar permisos según TREE + ACTIONS + ver_sucursal">
            Normalizar permisos
          </button>
        </form>
      </div>
    </div>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{ openAll:false, toggleAll(){ this.openAll = !this.openAll } }">

    {{-- Errores de validación --}}
    @if ($errors->any())
      <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-red-800">
        <div class="font-semibold mb-1">Revisa los siguientes errores:</div>
        <ul class="list-disc pl-5 space-y-0.5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- Flash messages --}}
    @if (session('ok'))
      <div class="mb-4 p-3 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">
        {{ session('ok') }}
      </div>
    @endif
    @if (session('warning'))
      <div class="mb-4 p-3 rounded bg-amber-50 text-amber-800 border border-amber-200">
        {{ session('warning') }}
      </div>
    @endif

    @php
      // Helper: ¿el rol tiene el permiso module.action?
      $has = function($role, $module, $action) use ($permIndex) {
        return isset($permIndex[$module][$action])
            && $role->hasPermissionTo($permIndex[$module][$action]);
      };
    @endphp

    {{-- ====== ACCORDION DE ROLES ====== --}}
    <div class="mb-6 flex justify-end">
      <button type="button"
              @click="toggleAll()"
              class="px-3 py-2 text-sm rounded-md bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100">
        <span x-text="openAll ? 'Colapsar todos' : 'Expandir todos'"></span>
      </button>
    </div>

    @foreach ($roles as $role)
      <div class="mb-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700"
           x-data="{ open: openAll }" x-effect="$watch('openAll', v => open = v)">

        {{-- Cabecera del acordeón --}}
        <button type="button"
                @click="open = !open"
                class="w-full px-4 py-3 flex items-center justify-between bg-gray-50 dark:bg-gray-900/40">
          <div class="font-semibold text-gray-800 dark:text-gray-100">
            Rol: <span class="text-indigo-600 dark:text-indigo-400">{{ $role->name }}</span>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 transition-transform"
               :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/>
          </svg>
        </button>

        {{-- Contenido del acordeón --}}
        <div x-show="open" x-collapse x-cloak>
          <form method="POST" action="{{ route('admin.permisos.updateRolePermissions', $role) }}">
            @csrf

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/30">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Módulo</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Crear</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Editar</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Eliminar</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ver</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Limitar a sucursal</th>
                  </tr>
                </thead>

                {{-- ====== CUERPO: grupos colapsables por MÓDULO ====== --}}
                @foreach ($tree as $groupTitle => $entries)
                  <tbody x-data="{ openGroup:false }"
                         @keydown.meta.k.stop.prevent.window="openGroup = !openGroup"
                         class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">

                    {{-- Fila cabecera del grupo/módulo --}}
                    <tr class="bg-gray-100/70 dark:bg-gray-900/40">
                      <td colspan="6" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                        <button type="button"
                                @click="openGroup = !openGroup"
                                class="inline-flex items-center gap-2 select-none">
                          <svg xmlns="http://www.w3.org/2000/svg"
                               class="h-4 w-4 text-gray-500 transition-transform"
                               :class="openGroup ? 'rotate-90' : ''"
                               viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 6l8 4-8 4V6z" clip-rule="evenodd"/>
                          </svg>
                          <span>{{ $groupTitle }}</span>
                          <span class="ml-2 text-xs text-gray-400" x-show="!openGroup" x-cloak>(contraído)</span>
                        </button>
                      </td>
                    </tr>

                    {{-- Entradas del grupo --}}
                    @foreach ($entries as $entry)
                      @php
                        $module = $entry['module'];
                        $label  = $entry['label'];
                      @endphp

                      <tr x-show="openGroup" x-collapse x-cloak>
                        {{-- Módulo --}}
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-800 dark:text-gray-100">
                          <span class="font-medium">{{ $label }}</span>
                          <span class="ml-2 text-xs text-gray-400">({{ $module }})</span>
                        </td>

                        {{-- Crear --}}
                        <td class="px-4 py-2 text-center">
                          <input type="checkbox"
                                 name="matrix[{{ $module }}][acciones][crear]"
                                 value="1"
                                 @checked($has($role,$module,'crear'))
                                 class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </td>

                        {{-- Editar --}}
                        <td class="px-4 py-2 text-center">
                          <input type="checkbox"
                                 name="matrix[{{ $module }}][acciones][editar]"
                                 value="1"
                                 @checked($has($role,$module,'editar'))
                                 class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </td>

                        {{-- Eliminar --}}
                        <td class="px-4 py-2 text-center">
                          <input type="checkbox"
                                 name="matrix[{{ $module }}][acciones][eliminar]"
                                 value="1"
                                 @checked($has($role,$module,'eliminar'))
                                 class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </td>

                        {{-- Ver --}}
                        <td class="px-4 py-2 text-center">
                          <input type="checkbox"
                                 name="matrix[{{ $module }}][acciones][ver]"
                                 value="1"
                                 @checked($has($role,$module,'ver'))
                                 class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </td>

                        {{-- Limitar a sucursal (✅ ver_sucursal) --}}
                        <td class="px-4 py-2">
                          <label class="inline-flex items-center gap-2">
                            <input type="checkbox"
                                   name="matrix[{{ $module }}][scope]"
                                   value="ver_sucursal"
                                   @checked($has($role,$module,'ver_sucursal'))
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Limitar a sucursal</span>
                          </label>

                          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Si está desmarcado y el rol tiene <code>{{ $module }}.ver</code>, verá todas.
                          </div>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                @endforeach
              </table>
            </div>

            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 flex justify-end">
              <button type="submit"
                      class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                Guardar cambios del rol "{{ $role->name }}"
              </button>
            </div>
          </form>
        </div>
      </div>
    @endforeach

    {{-- ====== USUARIOS Y ROLES ====== --}}
    <div class="mt-10">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-3">Usuarios y roles</h3>

      <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900/30">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roles</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($users as $user)
              <tr>
                <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-100">
                  <div class="font-medium">{{ $user->name }}</div>
                  <div class="text-xs text-gray-500">{{ $user->email }}</div>
                </td>

                <td class="px-4 py-2">
                  <form method="POST" action="{{ route('admin.permisos.syncUserRoles', $user) }}" class="flex items-center gap-2">
                    @csrf
                    <select name="roles[]" multiple
                            class="min-w-[16rem] rounded-md border-gray-300 dark:bg-gray-800 dark:text-gray-100 focus:ring-indigo-500 focus:border-indigo-500"
                            size="3">
                      @php $userRoles = $user->roles->pluck('name')->all(); @endphp
                      @foreach ($roles as $r)
                        <option value="{{ $r->name }}" @selected(in_array($r->name, $userRoles, true))>
                          {{ $r->name }}
                        </option>
                      @endforeach
                    </select>

                    <button type="submit"
                            class="px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                      Guardar
                    </button>
                  </form>
                </td>

                <td class="px-4 py-2 text-right text-sm text-gray-600 dark:text-gray-300">
                  {{-- Acciones adicionales si las necesitas --}}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <p class="text-xs text-gray-500 mt-2">
        Nota: si cambias tus propios roles, se te pedirá iniciar sesión de nuevo.
      </p>
    </div>
  </div>
</x-app-layout>
