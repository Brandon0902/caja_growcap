{{-- resources/views/user_data/form.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    @php $isEdit = $userData->exists; @endphp
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ $isEdit
          ? "Editar Datos de Cliente ({$cliente->nombre} {$cliente->apellido})"
          : "Nuevo Registro de Datos Cliente ({$cliente->nombre} {$cliente->apellido})"
      }}
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
    $active = request('tab','general');
    $formAction = route('clientes.datos.save', $cliente) . '?tab=' . $active;
  @endphp

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

    @if(session('success'))
      <div class="mb-4 rounded-lg bg-green-100 p-4 text-green-800 dark:bg-green-900 dark:text-green-200">
        {{ session('success') }}
      </div>
    @endif

    @if(session('error'))
      <div class="mb-4 rounded-lg bg-red-100 p-4 text-red-800 dark:bg-red-900 dark:text-red-200">
        {{ session('error') }}
      </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
      <nav class="flex border-b border-gray-200 dark:border-gray-700 px-4">
        @foreach($tabs as $key => $label)
          <a href="{{ route('clientes.datos.form', $cliente) }}?tab={{ $key }}"
             class="py-3 px-4 -mb-px border-b-2 font-medium text-sm
               {{ $active === $key
                  ? 'border-indigo-500 text-indigo-600 dark:text-indigo-300'
                  : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300' }}">
            {{ $label }}
          </a>
        @endforeach
      </nav>

      @if($active !== 'laborales')
        {{-- ✅ SOLO tabs normales usan el form general --}}
        <form action="{{ $formAction }}" method="POST" class="space-y-6 p-6">
          @csrf
          <input type="hidden" name="tab" value="{{ $active }}">

          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 space-y-6">
            @switch($active)
              @case('general')       @include('user_data.partials.general') @break
              @case('beneficiarios') @include('user_data.partials.beneficiarios') @break
              @case('banco')         @include('user_data.partials.banco') @break
              @case('seguridad')     @include('user_data.partials.seguridad') @break
              @case('acceso')        @include('user_data.partials.acceso') @break
              @default               @include('user_data.partials.general')
            @endswitch
          </div>

          <div class="text-right">
            <button type="submit"
                    class="px-4 py-2 {{ $isEdit ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-blue-600 hover:bg-blue-700' }} text-white rounded">
              {{ $isEdit ? 'Guardar cambios' : 'Guardar' }}
            </button>
          </div>
        </form>
      @else
        {{-- ✅ Laborales va FUERA del form general (evita forms anidados) --}}
        <div class="p-6">
          @include('user_data.partials.laborales')
        </div>
      @endif

    </div>
  </div>
</x-app-layout>
