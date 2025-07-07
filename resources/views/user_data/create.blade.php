{{-- resources/views/user_data/create.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Nuevo Registro de Datos Cliente') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <form action="{{ route('user_data.store') }}" method="POST">
      @csrf

      {{-- Cliente --}}
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Cliente</label>
        <select name="id_cliente"
                class="mt-1 block w-full border-gray-300 rounded">
          <option value="">— Selecciona —</option>
          @foreach($clientes as $id => $nombre)
            <option value="{{ $id }}" {{ old('id_cliente') == $id ? 'selected' : '' }}>
              {{ $nombre }}
            </option>
          @endforeach
        </select>
        @error('id_cliente') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
      </div>

      {{-- Estado / Municipio --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-4"
           x-data="{
             estado: '{{ old('id_estado','') }}',
             municipios: [],
             selectedMuni: '{{ old('id_municipio','') }}',
             loadMunicipios() {
               if (! this.estado) return;
               fetch('/municipios?estado=' + this.estado)
                 .then(r => r.json())
                 .then(list => this.municipios = list);
             }
           }"
           x-init="loadMunicipios()"
      >
        {{-- Estado --}}
        <div>
          <label class="block text-sm font-medium text-gray-700">Estado</label>
          <select name="id_estado"
                  x-model="estado"
                  @change="loadMunicipios()"
                  class="mt-1 block w-full border-gray-300 rounded">
            <option value="">— Selecciona —</option>
            @foreach($estados as $id => $nombre)
              <option value="{{ $id }}" {{ old('id_estado') == $id ? 'selected' : '' }}>
                {{ $nombre }}
              </option>
            @endforeach
          </select>
          @error('id_estado')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>

        {{-- Municipio --}}
        <div>
          <label class="block text-sm font-medium text-gray-700">Municipio</label>
          <select name="id_municipio"
                  x-model="selectedMuni"
                  class="mt-1 block w-full border-gray-300 rounded">
            <option value="">— Selecciona —</option>
            <template x-for="m in municipios" :key="m.id">
              <option :value="m.id" x-text="m.nombre"></option>
            </template>
          </select>
          @error('id_municipio')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        </div>
      </div>

      {{-- Partial: Beneficiarios --}}
      @include('user_data.partials.beneficiarios')

      {{-- Partial: Banco --}}
      @include('user_data.partials.banco')

      {{-- Partial: Seguridad --}}
      @include('user_data.partials.seguridad')

      {{-- Partial: Depósitos --}}
      @include('user_data.partials.depositos')

      <div class="mt-6">
        <button type="submit"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
          Guardar
        </button>
      </div>
    </form>
  </div>
</x-app-layout>
