{{-- resources/views/user_data/edit.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Editar Datos de Cliente') }}
      ({{ optional($userData->cliente)->nombre }} {{ optional($userData->cliente)->apellido }})
    </h2>
  </x-slot>

  @php
    $tabs = [
      'general'       => 'Datos Generales',
      'beneficiarios' => 'Beneficiarios',
      'banco'         => 'Banco',
      'seguridad'     => 'Seguridad',
      'acceso'        => 'Acceso',
      'laborales'     => 'Laborales',
    ];
    $active = request('tab', 'general');
  @endphp

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    @if(session('success'))
      <div class="mb-4 rounded-lg bg-green-100 p-4 text-green-800 dark:bg-green-900 dark:text-green-200">
        {{ session('success') }}
      </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
      {{-- Nav de pestañas --}}
      <nav class="flex border-b border-gray-200 dark:border-gray-700 px-4">
        @foreach($tabs as $key => $label)
          <a href="{{ route('user_data.edit', $userData) }}?tab={{ $key }}"
             class="py-3 px-4 -mb-px border-b-2 font-medium text-sm {{ $active === $key
                   ? 'border-indigo-500 text-indigo-600 dark:text-indigo-300'
                   : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300' }}">
            {{ $label }}
          </a>
        @endforeach
      </nav>

      @if($active === 'laborales')
        {{-- Pestaña Laborales --}}
        @include('user_data.partials.laborales')
      @else
        <form action="{{ route('user_data.update', $userData) }}?tab={{ $active }}" method="POST">
          @csrf
          @method('PUT')
          <input type="hidden" name="id_cliente" value="{{ $userData->id_cliente }}">
          <input type="hidden" name="tab" value="{{ $active }}">

          <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-b-lg">
            @switch($active)
              @case('general')
                @include('user_data.partials.general')
                @break

              @case('beneficiarios')
                @include('user_data.partials.beneficiarios')
                @break

              @case('banco')
                @include('user_data.partials.banco')
                @break

              @case('seguridad')
                @include('user_data.partials.seguridad')
                @break

              @case('acceso')
                @include('user_data.partials.acceso')
                @break

              @default
                @include('user_data.partials.general')
            @endswitch
          </div>

          <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 text-right">
            <button type="submit"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">
              Guardar cambios
            </button>
          </div>
        </form>
      @endif
    </div>
  </div>
</x-app-layout>
