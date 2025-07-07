<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      @if($userData->documento) Editar @else Subir @endif documentos de
      {{ optional($userData->cliente)->nombre }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <form action="{{ route('documentos.store', $userData) }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-6 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
      @csrf

      {{-- Aquí reutilizas tu diseño del partial --}}
      @include('user_data.partials.documentos')

      <div class="text-right">
        <button type="submit"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">
          Guardar Documentos
        </button>
      </div>
    </form>
  </div>
</x-app-layout>
