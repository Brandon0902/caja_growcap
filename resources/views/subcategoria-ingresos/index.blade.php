{{-- resources/views/subcategoria_ingresos/index.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Subcategorías de Ingreso') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="{ search: '' }">
    <div class="mb-4 flex justify-between">
      <a href="{{ route('subcategoria-ingresos.create') }}"
         class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded">
        + Nueva Subcategoría
      </a>
      <input type="text" x-model="search" placeholder="Buscar…"
             class="px-3 py-2 border rounded focus:ring-purple-500"/>
    </div>

    @if(session('success'))
      <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
        {{ session('success') }}
      </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-purple-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Nombre</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Categoría</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Creado por</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($subs as $s)
            <tr x-show="$el.textContent.toLowerCase().includes(search.toLowerCase())">
              <td class="px-6 py-4">{{ $s->nombre }}</td>
              <td class="px-6 py-4">{{ optional($s->categoria)->nombre }}</td>
              <td class="px-6 py-4">{{ optional($s->usuario)->name }}</td>
              <td class="px-6 py-4">{{ $s->created_at->format('Y-m-d H:i') }}</td>
              <td class="px-6 py-4 text-right space-x-2">
                <a href="{{ route('subcategoria-ingresos.show',$s) }}"
                   class="text-indigo-600 hover:text-indigo-900">Ver</a>
                <a href="{{ route('subcategoria-ingresos.edit',$s) }}"
                   class="text-yellow-600 hover:text-yellow-800">Editar</a>
                <form action="{{ route('subcategoria-ingresos.destroy',$s) }}"
                      method="POST" class="inline">
                  @csrf @method('DELETE')
                  <button type="button" class="text-red-600 hover:text-red-800 btn-delete"
                          data-id="{{ $s->id_sub_ing }}">Eliminar</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                No hay subcategorías registradas.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
      <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-right">
        {{ $subs->links() }}
      </div>
    </div>
  </div>

  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', () => {
        Swal.fire({
          title: 'Eliminar subcategoría?',
          text: 'No se podrá deshacer.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar',
        }).then(r => { if(r.isConfirmed) btn.closest('form').submit(); });
      });
    });
  </script>
</x-app-layout>
