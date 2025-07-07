{{-- resources/views/user_data/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
  @php
    $campos = [
      'documento_01'    => 'Comprobante de Domicilio',
      'documento_02'    => 'INE Frente',
      'documento_02_02' => 'INE Reversa',
      'documento_03'    => 'INE Beneficiario',
      'documento_04'    => 'INE Beneficiario 02',
      'documento_05'    => 'Contrato',
    ];
  @endphp

  <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow rounded-lg">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
            Cliente
          </th>
          @foreach($campos as $field => $label)
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
              {{ $label }}
            </th>
          @endforeach
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
            Fecha de carga
          </th>
          <th class="px-6 py-3"></th>
        </tr>
      </thead>
      <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        @foreach($datos as $userData)
          @php $doc = $userData->documento; @endphp
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
              {{ optional($userData->cliente)->nombre }}
              {{ optional($userData->cliente)->apellido }}
            </td>

            @foreach($campos as $field => $label)
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                @if($doc?->$field)
                  <a href="{{ Storage::url($doc->$field) }}" target="_blank"
                     class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                    {{ basename($doc->$field) }}
                  </a>
                @else
                  <span class="text-gray-400">—</span>
                @endif
              </td>
            @endforeach

            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
              {{ $doc?->fecha?->format('Y-m-d H:i') ?? '—' }}
            </td>

            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <a href="{{ route('user_data.edit', $userData) }}"
                 class="text-blue-600 hover:text-blue-900 dark:text-blue-400">Editar</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div class="px-6 py-4">
      {{ $datos->links() }}
    </div>
  </div>
</div>
@endsection
