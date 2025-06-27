<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Preguntas') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" x-data="{ search: '' }">
    <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
      <a href="{{ route('preguntas.create') }}"
         class="inline-flex items-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600
                text-white font-semibold rounded-md shadow-sm focus:outline-none
                focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400">
        + Nueva Pregunta
      </a>

      <input type="text" x-model="search" placeholder="{{ __('Buscar…') }}"
             class="px-3 py-2 border rounded-md shadow-sm
                    focus:outline-none focus:ring-2 focus:ring-purple-500
                    bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-200
                    dark:border-gray-600"/>
    </div>

    @if(session('success'))
      <div class="mb-4 rounded-lg bg-yellow-100 p-4 text-yellow-800
                  dark:bg-yellow-900 dark:text-yellow-200">
        {{ session('success') }}
      </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
      <div class="overflow-x-auto">
        <table class="table-auto min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
          <thead class="bg-purple-700 dark:bg-purple-900">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Pregunta</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Categoría</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Fecha</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase">Activo</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($preguntas as $p)
              <tr x-show="$el.textContent.toLowerCase().includes(search.toLowerCase())">
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $p->id }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ Str::limit($p->pregunta, 50) }}
                </td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">{{ $p->categoria }}</td>
                <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                  {{ optional($p->fecha)->format('d/m/Y H:i') }}
                </td>
                <td class="px-6 py-4 text-center">
                  @if($p->status == 1)
                    <span class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold text-yellow-800
                                 dark:bg-yellow-900 dark:text-yellow-200">Sí</span>
                  @else
                    <span class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold text-gray-800
                                 dark:bg-gray-700 dark:text-gray-300">No</span>
                  @endif
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  <a href="{{ route('preguntas.show', $p) }}"
                     class="inline-flex items-center text-purple-300 hover:text-purple-100">
                    <!-- icono “ver” -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 
                               2.943 9.542 7C20.268 16.057 16.477 19 12 19s-8.268
                               -2.943-9.542-7z"/>
                    </svg>
                  </a>
                  <a href="{{ route('preguntas.edit', $p) }}"
                     class="inline-flex items-center text-yellow-300 hover:text-yellow-100">
                    <!-- icono “editar” -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 
                               002 2h11a2 2 0 002-2v-5"/>
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 
                               1 1-4 9.5-9.5z"/>
                    </svg>
                  </a>
                  <form id="del-{{ $p->id }}" action="{{ route('preguntas.destroy', $p) }}" method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="button" data-id="{{ $p->id }}" class="btn-del inline-flex items-center p-2 rounded-full
                           bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 transition">
                      <!-- icono “borrar” -->
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white"
                           viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                              d="M6 2a2 2 0 00-2 2v1H2v2h1v10a2 2 
                                 0 002 2h10a2 2 0 002-2V7h1V5h-2V4
                                 a2 2 0 00-2-2H6zm2 3V4h4v1H8z"
                              clip-rule="evenodd"/>
                      </svg>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                  {{ __('No hay preguntas registradas.') }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $preguntas->links() }}
      </div>
    </div>
  </div>

  {{-- SweetAlert2 para eliminar --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.querySelectorAll('.btn-del').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        Swal.fire({
          title: '¿Eliminar esta pregunta?',
          text: '¡No podrás revertir esto!',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#d33',
          cancelButtonColor: '#aaa'
        }).then(result => {
          if (result.isConfirmed) {
            document.getElementById(`del-${id}`).submit();
          }
        });
      });
    });
  </script>
</x-app-layout>
