{{-- resources/views/adminuserprestamos/show.blade.php --}}
@php
use App\Models\UserData;
// Obtenemos el UserData asociado al cliente (contiene documentos del aval)
$ud = UserData::firstWhere('id_cliente', $cliente->id);
@endphp

<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __("Préstamos de") }} {{ $cliente->nombre }} {{ $cliente->apellido }}
      </h2>
      <div class="flex space-x-2">
        <a href="{{ route('user_prestamos.create') }}"
           class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700
                  text-white text-sm font-medium rounded-md shadow-sm focus:outline-none
                  focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
          {{ __('Crear Préstamo') }}
        </a>
        <a href="{{ route('user_prestamos.index') }}"
           class="inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600
                  text-white text-sm font-medium rounded-md shadow-sm focus:outline-none
                  focus:ring-2 focus:ring-offset-2 focus:ring-gray-400">
          {{ __('← Volver') }}
        </a>
      </div>
    </div>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    @if(session('success'))
      <div class="mb-4 rounded bg-green-100 p-4 text-green-800 dark:bg-green-900 dark:text-green-200">
        {{ session('success') }}
      </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto">
      <table class="table-auto min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
        <thead class="bg-green-700 dark:bg-green-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">#</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Cantidad</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Tipo</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Semanas</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Inicio</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Interés</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Abonos</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Mora</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Estado</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Aval</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Estado Aval</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Notificado Aval</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Documentos Aval</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($prestamos as $p)
            <tr>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ str_pad($p->id, 3, '0', STR_PAD_LEFT) }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ number_format($p->cantidad, 2) }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $p->tipo_prestamo }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $p->semanas }} semanas
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ \Carbon\Carbon::parse($p->fecha_inicio)->format('Y-m-d') }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $p->interes_generado }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $p->abonos_echos }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ number_format($p->mora_acumulada, 2) }}
              </td>
              <td class="px-6 py-4 text-gray-900 dark:text-white">
                {{ $statusOptions[$p->status] ?? '-' }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ optional($p->aval)->nombre ?? 'Sin aval' }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $statusOptions[$p->aval_status] ?? '-' }}
              </td>
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                {{ $p->aval_responded_at
                   ? \Carbon\Carbon::parse($p->aval_responded_at)->format('Y-m-d H:i')
                   : '—' }}
              </td>
              {{-- Documentos del aval, apilados verticalmente --}}
              <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                <div class="flex flex-col space-y-1 w-max">
                  @if($ud)
                    @php
                      $fields = [
                        'doc_solicitud_aval'        => 'Solicitud',
                        'doc_comprobante_domicilio' => 'Domicilio',
                        'doc_ine_frente'            => 'INE Frente',
                        'doc_ine_reverso'           => 'INE Reverso',
                      ];
                    @endphp
                    @foreach($fields as $field => $label)
                      @if($ud->documento && $ud->documento->{$field})
                        <a href="{{ route('documentos.view', [$ud, $field]) }}"
                           target="_blank"
                           class="block px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded">
                          {{ $label }}
                        </a>
                      @else
                        <a href="{{ route('documentos.create', $ud) }}?field={{ $field }}"
                           class="block px-2 py-1 bg-gray-500 hover:bg-gray-600 text-white text-xs font-medium rounded">
                          {{ $label }}
                        </a>
                      @endif
                    @endforeach
                  @endif
                </div>
              </td>
              <td class="px-6 py-4 text-right">
                <a href="{{ route('user_prestamos.edit', $p) }}"
                   class="inline-block px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded">
                  {{ __('Editar') }}
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="14"
                  class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                {{ __('Este cliente no tiene préstamos.') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right sm:px-6">
        {{ $prestamos->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
