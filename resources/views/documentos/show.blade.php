{{-- resources/views/documentos/show.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
      Documentos de {{ optional($userData->cliente)->nombre }}
      {{ optional($userData->cliente)->apellido }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
    @if(session('success'))
      <div class="rounded-lg bg-green-100 dark:bg-green-900 p-4 text-green-800 dark:text-green-200">
        {{ session('success') }}
      </div>
    @endif

    @php
      // Asumiendo que $userData->documento es tu relación
      $doc = $userData->documento;
    @endphp

    <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Campo</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Archivo</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Ver</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Descargar</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Eliminar</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @foreach([
            'documento_01'    => 'Comprobante de Domicilio',
            'documento_02'    => 'INE Frente',
            'documento_02_02' => 'INE Reversa',
            'documento_03'    => 'INE Beneficiario',
            'documento_04'    => 'INE Beneficiario 02',
            'documento_05'    => 'Contrato',
          ] as $field => $label)
            <tr>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-gray-200">
                {{ $label }}
              </td>

              @if($doc && $doc->$field)
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                  {{ basename($doc->$field) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <a href="{{ route('documentos.view', [$userData, $field]) }}"
                     target="_blank"
                     class="text-indigo-600 dark:text-indigo-400 hover:underline">
                    👁️
                  </a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <a href="{{ route('documentos.download', [$userData, $field]) }}"
                     class="text-blue-600 dark:text-blue-400 hover:underline">
                    📥
                  </a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <form action="{{ route('documentos.destroyField', [$userData, $field]) }}"
                        method="POST"
                        onsubmit="return confirm('¿Eliminar este documento?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200">
                      🗑️
                    </button>
                  </form>
                </td>
              @else
                <td class="px-6 py-4 whitespace-nowrap text-sm italic text-gray-400 dark:text-gray-500" colspan="4">
                  (No cargado)
                </td>
              @endif
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="flex space-x-4">
      <a href="{{ route('documentos.create', $userData) }}"
         class="px-4 py-2 bg-green-600 dark:bg-green-500 hover:bg-green-700 dark:hover:bg-green-600 text-white rounded shadow">
        @if($doc) Editar documentos @else Subir documentos @endif
      </a>
      <a href="{{ route('documentos.index') }}"
         class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded shadow">
        ← Volver al listado
      </a>
    </div>
  </div>
</x-app-layout>
