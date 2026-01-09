<x-app-layout>
  <x-slot name="header">
    <h2 class="text-xl font-semibold text-white">Gestión de Metas y Presupuestos</h2>
  </x-slot>

  <div class="p-6 space-y-6">

    {{-- Alerts --}}
    @if (session('success'))
      <div class="p-3 rounded bg-green-100 text-green-800">
        {{ session('success') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="p-3 rounded bg-red-100 text-red-800">
        <ul class="list-disc list-inside">
          @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('presupuestos.store') }}" method="POST" class="space-y-4">
      @csrf

      {{-- Selección Mes/Año --}}
      <div class="flex flex-col sm:flex-row gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Mes</label>
          <select name="mes" class="border rounded px-2 py-1 bg-white dark:bg-gray-800">
            @foreach(range(1,12) as $m)
              <option value="{{ $m }}" @selected($m == $mes)>{{ $meses[$m] ?? $m }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Año</label>
          <select name="año" class="border rounded px-2 py-1 bg-white dark:bg-gray-800">
            @foreach(range(now()->year, now()->year-5) as $y)
              <option value="{{ $y }}" @selected($y == $año)>{{ $y }}</option>
            @endforeach
          </select>
        </div>
      </div>

      {{-- Inputs por Fuente (Tipo + Monto) --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($fuentes as $f)
          @php
            $row = $presupuestos[$f] ?? null;
            $tipoInicial = $row->tipo ?? ($tiposPorDefecto[$f] ?? 'Presupuesto');
          @endphp

          <div class="border rounded p-3 bg-white dark:bg-gray-800">
            <label class="font-semibold block mb-2">{{ $f }}</label>

            {{-- Mantener índices alineados --}}
            <input type="hidden" name="fuente[]" value="{{ $f }}" />

            <div class="flex items-center gap-2 mb-2">
              <label class="text-sm w-20">Tipo</label>
              <select name="tipo[]" class="border rounded px-2 py-1 flex-1 bg-white dark:bg-gray-900">
                @foreach($tipos as $t)
                  <option value="{{ $t }}" @selected($tipoInicial === $t)>{{ $t }}</option>
                @endforeach
              </select>
            </div>

            <div class="flex items-center gap-2">
              <label class="text-sm w-20">Monto</label>
              <input
                type="number"
                step="0.01"
                name="monto[]"
                value="{{ $row->monto ?? '' }}"
                placeholder="0.00"
                class="border rounded px-2 py-1 flex-1 bg-white dark:bg-gray-900"
              />
            </div>
          </div>
        @endforeach
      </div>

      <div>
        <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">
          Guardar
        </button>
      </div>
    </form>
  </div>
</x-app-layout>
