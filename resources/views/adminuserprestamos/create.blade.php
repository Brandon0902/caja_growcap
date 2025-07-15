@php
    $plansJs = $planes->map(fn($p) => [
        'id'      => $p->id_prestamo,
        'periodo' => $p->periodo,
        'semanas' => $p->semanas,
        'interes' => $p->interes,
    ]);
@endphp

<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Crear Préstamo (Admin)') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    @if ($errors->any())
      <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">
        <ul class="list-disc list-inside">
          @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('user_prestamos.store') }}"
          method="POST"
          enctype="multipart/form-data"
          x-data='{
            plans: @json($plansJs),
            selectedPlan: {{ json_encode(old("id_activo")) }},
            cantidad:     {{ json_encode(old("cantidad")) }},
            get periodo() {
              const p = this.plans.find(x => x.id === this.selectedPlan);
              return p ? p.periodo : "";
            },
            get semanas() {
              return this.plans.find(x => x.id === this.selectedPlan)?.semanas || "";
            },
            get interes() {
              const p = this.plans.find(x => x.id === this.selectedPlan);
              return p ? p.interes + "%" : "";
            },
            get interesGen() {
              if (! this.cantidad || ! this.interes) return "";
              return ((this.cantidad * parseFloat(this.interes)) / 100).toFixed(2);
            }
          }'>
      @csrf

      {{-- Cliente --}}
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
          {{ __('Cliente') }}
        </label>
        <select name="id_cliente" required
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                       bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200">
          <option value="">{{ __('-- Seleccione Cliente --') }}</option>
          @foreach ($clientes as $c)
            <option value="{{ $c->id }}" @selected(old('id_cliente') == $c->id)>
              {{ $c->nombre }} {{ $c->apellido }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Plan --}}
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
          {{ __('Plan') }}
        </label>
        <select name="id_activo"
                x-model.number="selectedPlan"
                required
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                       bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200">
          <option value="">{{ __('-- Seleccione Plan --') }}</option>
          @foreach ($planes as $p)
            <option value="{{ $p->id_prestamo }}" @selected(old('id_activo') == $p->id_prestamo)>
              {{ $p->periodo }} ({{ $p->semanas }} {{ __('semanas') }}, {{ $p->interes }}%)
            </option>
          @endforeach
        </select>
      </div>

      {{-- Datos dinámicos --}}
      <div class="mb-4 grid grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Periodo') }}</label>
          <input x-model="periodo" disabled
                 class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                        bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200"/>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Semanas') }}</label>
          <input x-model="semanas" disabled
                 class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                        bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200"/>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Interés %') }}</label>
          <input x-model="interes" disabled
                 class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                        bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200"/>
        </div>
      </div>

      {{-- Fecha de inicio --}}
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Fecha de inicio') }}</label>
        <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}" required
               class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                      bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200"/>
      </div>

      {{-- Cantidad e interés generado --}}
      <div class="mb-4 grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Cantidad a solicitar') }}</label>
          <input type="number" name="cantidad" x-model.number="cantidad" step="0.01" required
                 class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                        bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200"/>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Interés generado') }}</label>
          <input :value="interesGen" disabled
                 class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                        bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200"/>
        </div>
      </div>

      <hr class="my-4"/>

      {{-- Código de Aval --}}
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Código de Aval') }}</label>
        <input type="text" name="codigo_aval" value="{{ old('codigo_aval') }}"
               class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                      bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200"/>
        <p class="text-xs italic text-gray-500 dark:text-gray-400">
          {{ __('Si ingresa código de aval no es necesario subir documentos.') }}
        </p>
      </div>

      {{-- Documentos --}}
      <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Solicitud de Aval') }}</label>
          <input type="file" name="doc_solicitud_aval"
                 class="mt-1 block w-full text-gray-700 dark:text-gray-200"/>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Comprobante de Domicilio') }}</label>
          <input type="file" name="doc_comprobante_domicilio"
                 class="mt-1 block w-full text-gray-700 dark:text-gray-200"/>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('INE Frente') }}</label>
          <input type="file" name="doc_ine_frente"
                 class="mt-1 block w-full text-gray-700 dark:text-gray-200"/>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('INE Reverso') }}</label>
          <input type="file" name="doc_ine_reverso"
                 class="mt-1 block w-full text-gray-700 dark:text-gray-200"/>
        </div>
      </div>

      {{-- Botón Crear --}}
      <div class="flex justify-end">
        <button class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md shadow">
          {{ __('Crear Nuevo Préstamo') }}
        </button>
      </div>
    </form>
  </div>
</x-app-layout>
