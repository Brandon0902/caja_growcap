{{-- resources/views/usuarios/_content.blade.php --}}
@unless($panel ?? false)
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Usuarios') }}
    </h2>
  </x-slot>
@endunless

<style>[x-cloak]{display:none!important}</style>

<div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
     x-data="{
       search: '',
       filterRol: '',
       typing: false,
       clear() { this.search=''; this.filterRol=''; }
     }">

  {{-- Éxito --}}
  @if(session('success'))
    <div class="mb-4 rounded-lg bg-yellow-100 p-4 text-yellow-800
                dark:bg-yellow-900 dark:text-yellow-200">
      {{ session('success') }}
    </div>
  @endif

  {{-- Acciones y filtros --}}
  <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
    <a href="{{ route('usuarios.create') }}"
       class="inline-flex items-center px-4 py-2
              bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-700 dark:hover:bg-yellow-800
              text-white font-semibold rounded-md shadow-sm focus:outline-none
              focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
      + {{ __('Nuevo Usuario') }}
    </a>

    <div class="flex items-center gap-2 w-full sm:w-auto">
      <div class="relative flex-1 sm:w-72">
        <input type="text"
               x-model.debounce.400ms="search"
               @input="typing = true; setTimeout(()=>typing=false, 350)"
               placeholder="{{ __('Buscar por nombre/email/rol…') }}"
               class="w-full px-3 py-2 border rounded-md shadow-sm
                      focus:outline-none focus:ring-2 focus:ring-purple-500
                      bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                      dark:border-gray-600"/>
        <svg x-cloak x-show="typing" class="h-5 w-5 animate-spin absolute right-3 top-2.5 opacity-70" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity=".25"></circle>
          <path d="M22 12a10 10 0 0 1-10 10" fill="currentColor"></path>
        </svg>
      </div>

      <select x-model="filterRol"
              class="px-3 py-2 sm:w-48 border rounded-md shadow-sm
                     focus:outline-none focus:ring-2 focus:ring-purple-500
                     bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                     dark:border-gray-600">
        <option value="">{{ __('Todos los roles') }}</option>
        @foreach($roles as $rol)
          <option value="{{ $rol }}">{{ ucfirst($rol) }}</option>
        @endforeach
      </select>

      <button @click="clear()"
              class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded-md">
        {{ __('Limpiar') }}
      </button>
    </div>
  </div>

  {{-- Tabla --}}
  <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
    <div class="overflow-x-auto">
      <table class="table-auto min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
        <thead class="bg-purple-700 dark:bg-purple-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Nombre') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Email') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Rol') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Creación') }}</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase">{{ __('Activo') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">{{ __('Acciones') }}</th>
          </tr>
        </thead>

        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($usuarios as $u)
            <tr
              x-show="
                (
                  '{{ Str::lower($u->name) }} {{ Str::lower($u->email) }} {{ Str::lower($u->rol) }}'
                ).includes(search.toLowerCase())
                && (!filterRol || filterRol === '{{ $u->rol }}')
              "
              class="last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $u->name }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $u->email }}</td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                <span class="px-2 py-0.5 rounded text-xs font-semibold
                             bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                  {{ ucfirst($u->rol) }}
                </span>
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ optional($u->fecha_creacion)->format('Y-m-d H:i') ?? '—' }}
              </td>
              <td class="px-6 py-4 text-center">
                @if($u->activo)
                  <span class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold text-yellow-800
                               dark:bg-yellow-900 dark:text-yellow-200">Sí</span>
                @else
                  <span class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold text-gray-800
                               dark:bg-gray-700 dark:text-gray-300">No</span>
                @endif
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                {{-- Ver --}}
                <a href="{{ route('usuarios.show', $u) }}"
                   class="inline-flex items-center text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-100">
                  <svg xmlns="http://www.w3.org/2000/svg"
                       class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
                       stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7
                             C20.268 16.057 16.477 19 12 19s-8.268-2.943-9.542-7z" />
                  </svg>
                  {{ __('Ver') }}
                </a>

                {{-- Editar --}}
                <a href="{{ route('usuarios.edit', $u) }}"
                   class="inline-flex items-center text-yellow-600 hover:text-yellow-800 dark:text-yellow-300 dark:hover:text-yellow-100">
                  <svg xmlns="http://www.w3.org/2000/svg"
                       class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
                       stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0
                             002 2h11a2 2 0 002-2v-5" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4
                             9.5-9.5z" />
                  </svg>
                  {{ __('Editar') }}
                </a>

                {{-- Activar / Desactivar --}}
                <form id="toggle-form-{{ $u->id_usuario }}"
                      action="{{ route('usuarios.toggle', $u) }}"
                      method="POST" class="inline">
                  @csrf @method('PATCH')
                  <button type="button"
                          data-id="{{ $u->id_usuario }}"
                          data-active="{{ $u->activo ? '1' : '0' }}"
                          class="btn-toggle inline-flex items-center p-2 rounded-full transition
                                 {{ $u->activo
                                   ? 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600'
                                   : 'bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600' }}">
                    @if($u->activo)
                      <svg xmlns="http://www.w3.org/2000/svg"
                           class="h-5 w-5 text-white" viewBox="0 0 20 20"
                           fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm-2.293-9.707a1 1 0
                                 011.414 0L10 8.586l1.293-1.293a1 1 0
                                 111.414 1.414L11.414 10l1.293 1.293a1 1 0
                                 01-1.414 1.414L10 11.414l-1.293 1.293a1 1 0
                                 01-1.414-1.414L8.586 10l-1.293-1.293a1 1 0
                                 010-1.414z"
                              clip-rule="evenodd" />
                      </svg>
                    @else
                      <svg xmlns="http://www.w3.org/2000/svg"
                           class="h-5 w-5 text-white" viewBox="0 0 20 20"
                           fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0
                                 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0
                                 00-1.414 1.414l2 2a1 1 0
                                 001.414 0l4-4z"
                              clip-rule="evenodd" />
                      </svg>
                    @endif
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                {{ __('No hay usuarios registrados.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Paginación --}}
    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
      {{ $usuarios->links() }}
    </div>
  </div>
</div>

{{-- SweetAlert2 para activar/inactivar --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const id     = btn.dataset.id,
            active = btn.dataset.active === '1',
            verb   = active ? 'desactivar' : 'activar';

      Swal.fire({
        title: `¿Deseas ${verb} este usuario?`,
        text:  `Se marcará como ${ active ? 'inactivo' : 'activo' }.`,
        icon:  'question',
        showCancelButton: true,
        confirmButtonText: `Sí, ${verb}`,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: active ? '#d33' : '#3085d6',
        cancelButtonColor: '#aaa'
      }).then(result => {
        if (result.isConfirmed) {
          document.getElementById(`toggle-form-${id}`).submit();
        }
      });
    });
  });
</script>
