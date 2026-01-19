{{-- resources/views/depositos/show.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Depósito #') }}{{ str_pad($deposito->id, 3, '0', STR_PAD_LEFT) }}
      </h2>
      <a href="{{ route('depositos.index') }}"
         class="inline-flex items-center px-3 py-2 bg-gray-500 hover:bg-gray-600
                text-white text-sm font-medium rounded-md shadow-sm">
        {{ __('← Volver al listado') }}
      </a>
    </div>
  </x-slot>

  @php
    // ===== Detección Stripe (sin depender de Schema en Blade) =====
    $paymentMethod = strtolower((string)($deposito->payment_method ?? ''));
    $stripeStatus  = strtolower((string)($deposito->stripe_status ?? ''));
    $payStatus     = strtolower((string)($deposito->payment_status ?? ''));

    $isStripe = ($paymentMethod === 'stripe')
      || !empty($deposito->stripe_checkout_session_id)
      || !empty($deposito->stripe_payment_intent_id)
      || !empty($deposito->stripe_status)
      || !empty($deposito->payment_status);

    $isStripePaid = in_array($payStatus, ['paid','succeeded'], true)
      || in_array($stripeStatus, ['paid','succeeded'], true);

    // Deshabilitar aprobar solo cuando NO está aprobado todavía y NO está paid
    $disableApprove = $isStripe && !$isStripePaid && (int)$deposito->status !== 1;

    // Movimiento de caja (si ya existe)
    $mov = \App\Models\MovimientoCaja::where('origen_id', $deposito->id)
      ->where('tipo_mov', 'Ingreso')
      ->latest('fecha')
      ->first();
  @endphp

  <div class="py-6 mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
    @if(session('success'))
      <div class="mb-4 rounded-lg bg-green-100 dark:bg-green-900 p-4 text-green-800 dark:text-green-200">
        {{ session('success') }}
      </div>
    @endif

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

    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">

      {{-- ===== Banner Stripe (si aplica) ===== --}}
      @if($isStripe)
        <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-indigo-900
                    dark:border-indigo-900/50 dark:bg-indigo-900/20 dark:text-indigo-200">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="font-semibold">Pago con tarjeta (Stripe)</div>
              <div class="text-sm opacity-90">
                Este depósito se creó vía Checkout. El admin solo debe aprobar cuando el pago esté confirmado (paid).
              </div>
            </div>

            @if($isStripePaid)
              <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                           bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                PAID
              </span>
            @else
              <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                           bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                NO CONFIRMADO
              </span>
            @endif
          </div>

          <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="rounded-md bg-white/70 dark:bg-gray-900/40 p-3">
              <div class="text-xs text-gray-600 dark:text-gray-400">payment_status</div>
              <div class="font-mono">{{ $deposito->payment_status ?? '—' }}</div>
            </div>
            <div class="rounded-md bg-white/70 dark:bg-gray-900/40 p-3">
              <div class="text-xs text-gray-600 dark:text-gray-400">stripe_status</div>
              <div class="font-mono">{{ $deposito->stripe_status ?? '—' }}</div>
            </div>
            <div class="rounded-md bg-white/70 dark:bg-gray-900/40 p-3 md:col-span-2">
              <div class="text-xs text-gray-600 dark:text-gray-400">stripe_checkout_session_id</div>
              <div class="font-mono break-all">{{ $deposito->stripe_checkout_session_id ?? '—' }}</div>
            </div>
            <div class="rounded-md bg-white/70 dark:bg-gray-900/40 p-3 md:col-span-2">
              <div class="text-xs text-gray-600 dark:text-gray-400">stripe_payment_intent_id</div>
              <div class="font-mono break-all">{{ $deposito->stripe_payment_intent_id ?? '—' }}</div>
            </div>

            @if(!empty($deposito->fecha_pago))
              <div class="rounded-md bg-white/70 dark:bg-gray-900/40 p-3 md:col-span-2">
                <div class="text-xs text-gray-600 dark:text-gray-400">fecha_pago</div>
                <div class="font-mono break-all">{{ $deposito->fecha_pago }}</div>
              </div>
            @endif
          </div>

          @if(!$isStripePaid)
            <div class="mt-3 text-sm text-amber-800 dark:text-amber-200">
              ⚠️ Aún no está confirmado como <b>paid</b>. Espera webhook / revisión antes de aprobar.
            </div>
          @endif
        </div>
      @endif

      {{-- Datos del depósito --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Cliente') }}</h3>
          <p class="text-base text-gray-800 dark:text-gray-100">
            {{ optional($deposito->cliente)->nombre }} {{ optional($deposito->cliente)->apellido }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ optional($deposito->cliente)->email }}</p>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Monto') }}</h3>
          <p class="text-base text-gray-800 dark:text-gray-100">${{ number_format($deposito->cantidad,2) }}</p>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Fecha de depósito') }}</h3>
          <p class="text-base text-gray-800 dark:text-gray-100">
            {{ \Carbon\Carbon::parse($deposito->fecha_deposito)->format('Y-m-d') }}
          </p>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Caja asociada') }}</h3>
          <p class="text-base text-gray-800 dark:text-gray-100">{{ optional($deposito->caja)->nombre ?? '—' }}</p>
        </div>

        <div class="md:col-span-2">
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Nota') }}</h3>
          <p class="text-base text-gray-800 dark:text-gray-100">{{ $deposito->nota ?: '—' }}</p>
        </div>

        {{-- Movimiento de caja (si ya existe) --}}
        <div class="md:col-span-2">
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Movimiento en caja') }}</h3>

          @if($mov)
            <div class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 p-4
                        dark:border-emerald-900/50 dark:bg-emerald-900/20">
              <div class="flex items-center justify-between gap-3">
                <div class="text-sm text-emerald-900 dark:text-emerald-200">
                  ✅ Ya existe un movimiento de caja para este depósito.
                </div>
                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold
                             bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                  Ingreso
                </span>
              </div>

              <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-800 dark:text-gray-100">
                <div class="rounded-md bg-white/70 dark:bg-gray-900/40 p-3">
                  <div class="text-xs text-gray-600 dark:text-gray-400">origen_id</div>
                  <div class="font-mono">{{ $mov->origen_id }}</div>
                </div>
                <div class="rounded-md bg-white/70 dark:bg-gray-900/40 p-3">
                  <div class="text-xs text-gray-600 dark:text-gray-400">proveedor_id</div>
                  <div class="font-mono">{{ $mov->proveedor_id ?? '—' }}</div>
                </div>
                <div class="rounded-md bg-white/70 dark:bg-gray-900/40 p-3">
                  <div class="text-xs text-gray-600 dark:text-gray-400">monto</div>
                  <div class="font-mono">${{ number_format((float)$mov->monto, 2) }}</div>
                </div>
                <div class="rounded-md bg-white/70 dark:bg-gray-900/40 p-3">
                  <div class="text-xs text-gray-600 dark:text-gray-400">fecha</div>
                  <div class="font-mono">{{ $mov->fecha }}</div>
                </div>
              </div>
            </div>
          @else
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">
              — Aún no hay movimiento. Se creará automáticamente al aprobar (status=1).
            </p>
          @endif
        </div>

        {{-- Comprobante (preview + descarga) --}}
        <div class="md:col-span-2 space-y-2">
          <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('Comprobante') }}</h3>

          @if(!empty($archivoUrl))
            @php
              $ext = strtolower(pathinfo(parse_url($archivoUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
            @endphp

            @if(in_array($ext, ['jpg','jpeg','png','gif','webp']))
              <img src="{{ $archivoUrl }}" alt="Comprobante de depósito"
                   class="max-h-96 rounded-md ring-1 ring-gray-200 dark:ring-gray-700">
            @elseif($ext === 'pdf')
              <iframe src="{{ $archivoUrl }}" class="w-full h-96 rounded-md ring-1 ring-gray-200 dark:ring-gray-700"></iframe>
            @else
              <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ __('Archivo adjunto disponible.') }}
              </p>
            @endif

            <a href="{{ $archivoUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
              {{ __('Abrir / Descargar') }}
            </a>
          @else
            <p class="text-base text-gray-800 dark:text-gray-100">—</p>
          @endif
        </div>
      </div>

      <hr class="border-gray-200 dark:border-gray-700">

      {{-- Form de actualización --}}
      <form method="POST"
            action="{{ route('depositos.update', $deposito) }}"
            x-data="{ st: '{{ old('status', (string)$deposito->status) }}' }"
            class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        @method('PATCH')

        {{-- Status --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ __('Status') }}
          </label>

          <select name="status"
                  x-model="st"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600
                         bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm
                         focus:border-green-500 focus:ring-green-500">
            @foreach($statusOptions as $value => $label)
              @php
                $isApproveOption = ((string)$value === '1' || (int)$value === 1);
                $shouldDisable   = $isApproveOption && $disableApprove;
              @endphp
              <option value="{{ $value }}"
                      @selected(old('status', $deposito->status) == $value)
                      @disabled($shouldDisable)
              >
                {{ $label }}@if($shouldDisable) — (Stripe no confirmado) @endif
              </option>
            @endforeach
          </select>

          @if($disableApprove)
            <p class="mt-1 text-xs text-amber-700 dark:text-amber-200">
              Este depósito es Stripe y aún no está <b>paid</b>; no se permite aprobar.
            </p>
          @endif
        </div>

        {{-- Caja (solo si se aprueba) --}}
        <div x-show="parseInt(st) === 1" x-cloak>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ __('Caja (requerida al aprobar)') }}
          </label>
          <select name="id_caja"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600
                         bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm
                         focus:border-green-500 focus:ring-green-500">
            <option value="">{{ __('— Selecciona caja —') }}</option>
            @foreach(($cajas ?? []) as $caja)
              <option value="{{ $caja->id_caja }}"
                @selected(old('id_caja', $deposito->id_caja) == $caja->id_caja)>
                {{ $caja->nombre }}
              </option>
            @endforeach
          </select>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ __('Al aprobar, se generará el movimiento en esta caja.') }}
          </p>
        </div>

        {{-- Nota --}}
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ __('Nota (opcional)') }}
          </label>
          <input type="text" name="nota" value="{{ old('nota', $deposito->nota) }}"
                 class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm
                        focus:border-green-500 focus:ring-green-500" />
        </div>

        <div class="md:col-span-3 flex justify-end">
          <button type="submit"
                  class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">
            {{ __('Guardar cambios') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
