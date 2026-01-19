<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Editar inversión #') }}{{ str_pad($inversion->id, 3, '0', STR_PAD_LEFT) }}
      </h2>

      <div class="flex gap-2">
        <a href="{{ route('user_inversiones.show', $inversion) }}"
           class="inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600
                  text-white text-sm font-medium rounded-md shadow-sm focus:outline-none
                  focus:ring-2 focus:ring-offset-2 focus:ring-gray-400">
          {{ __('← Ver detalle') }}
        </a>
        <a href="{{ route('user_inversiones.index') }}"
           class="inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600
                  text-white text-sm font-medium rounded-md shadow-sm focus:outline-none
                  focus:ring-2 focus:ring-offset-2 focus:ring-gray-400">
          {{ __('Listado') }}
        </a>
      </div>
    </div>
  </x-slot>

  <div class="py-6 mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
    @if ($errors->any())
      <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-800 dark:bg-red-900/40 dark:text-red-200">
        <div class="font-semibold mb-1">{{ __('Hay errores en el formulario:') }}</div>
        <ul class="list-disc ml-5 text-sm">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('user_inversiones.update', $inversion) }}" class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
      @csrf
      @method('PUT')

      <div class="p-6 space-y-6">
        {{-- Resumen (solo lectura) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          {{-- ✅ CLIENTE (FIX: asegurar relación y fallback por id_cliente) --}}
          <div>
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Cliente') }}</h3>

            @php
              // 1) Asegura que venga la relación (sin tocar controlador)
              $inversion->loadMissing('cliente');

              // 2) Si aún no viene, fallback directo por id_cliente (por si el modelo usa otra llave)
              $cli = $inversion->cliente;
              if (!$cli && !empty($inversion->id_cliente)) {
                $cli = \App\Models\Cliente::select('id','nombre','apellido','email')
                  ->where('id', $inversion->id_cliente)
                  ->first();
              }

              $nombre = trim(($cli->nombre ?? '').' '.($cli->apellido ?? ''));
              $email  = $cli->email ?? null;
            @endphp

            <p class="text-base text-gray-800 dark:text-gray-100">
              {{ $nombre !== '' ? $nombre : '—' }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ $email ?: '—' }}
            </p>
          </div>

          <div>
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Plan') }}</h3>
            <p class="text-base text-gray-800 dark:text-gray-100">
              {{ optional($inversion->plan)->periodo }} {{ __('meses') }}
              @php
                $tasa = $inversion->rendimiento ?? optional($inversion->plan)->rendimiento;
              @endphp
              — {{ number_format((float)$tasa, 2) }}%
            </p>
          </div>

          <div>
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Monto') }}</h3>
            <p class="text-base text-gray-800 dark:text-gray-100">
              ${{ number_format($inversion->inversion ?? 0, 2) }}
            </p>
          </div>

          {{-- ✅ FECHAS (igual como lo tenías) --}}
          <div>
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Fechas') }}</h3>

            <p class="text-sm text-gray-800 dark:text-gray-100">
              {{ __('Solicitud:') }}
              {{ $inversion->fecha_solicitud ? \Carbon\Carbon::parse($inversion->fecha_solicitud)->format('Y-m-d H:i') : '—' }}
            </p>

            <p class="text-sm text-gray-800 dark:text-gray-100">
              {{ __('Inicio:') }}
              {{ $inversion->fecha_inicio ? \Carbon\Carbon::parse($inversion->fecha_inicio)->format('Y-m-d') : '—' }}
            </p>

            <p class="text-sm text-gray-800 dark:text-gray-100">
              {{ __('Fin:') }}
              {{ $inversion->fecha_fin ? \Carbon\Carbon::parse($inversion->fecha_fin)->format('Y-m-d') : '—' }}
            </p>

            @if($inversion->fecha_respuesta)
              <p class="text-sm text-gray-800 dark:text-gray-100">
                {{ __('Última respuesta:') }} {{ \Carbon\Carbon::parse($inversion->fecha_respuesta)->format('Y-m-d H:i') }}
              </p>
            @endif
          </div>
        </div>

        <hr class="border-gray-200 dark:border-gray-700">

        {{-- Campos editables --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          {{-- Estatus --}}
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              {{ __('Estatus') }}
            </label>
            <select id="status" name="status"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600
                           bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm
                           focus:border-purple-500 focus:ring-purple-500">
              @foreach($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $inversion->status) == $value)>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Caja --}}
          <div>
            <label for="id_caja" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              {{ __('Caja asociada') }}
            </label>
            <select id="id_caja" name="id_caja"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600
                           bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm
                           focus:border-purple-500 focus:ring-purple-500" required>
              <option value="" disabled {{ old('id_caja', $inversion->id_caja) ? '' : 'selected' }}>
                {{ __('Selecciona una caja…') }}
              </option>
              @foreach($cajas as $caja)
                <option value="{{ $caja->id_caja }}" @selected(old('id_caja', $inversion->id_caja) == $caja->id_caja)>
                  {{ $caja->nombre ?? ('Caja #'.$caja->id_caja) }}
                </option>
              @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              {{ __('Requerida para movimientos: Activa (egreso) e Inactiva (ingreso).') }}
            </p>
          </div>

          {{-- Nota --}}
          <div class="md:col-span-2">
            <label for="nota" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
              {{ __('Nota (opcional)') }}
            </label>
            <textarea id="nota" name="nota" rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600
                             bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm
                             focus:border-purple-500 focus:ring-purple-500"
                      placeholder="{{ __('Escribe una nota interna…') }}">{{ old('nota', $inversion->nota) }}</textarea>
          </div>
        </div>
      </div>

      <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex items-center justify-end gap-2 rounded-b-lg">
        <a href="{{ route('user_inversiones.show', $inversion) }}"
           class="inline-flex items-center px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300
                  text-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-100">
          {{ __('Cancelar') }}
        </a>
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700
                       text-white shadow focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-400">
          {{ __('Guardar cambios') }}
        </button>
      </div>
    </form>
  </div>
</x-app-layout>
