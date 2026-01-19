{{-- resources/views/tickets/partials/results.blade.php --}}
<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto" id="tickets-results">
  <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
    <thead class="bg-purple-700 dark:bg-purple-900">
      <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">#</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Cliente') }}</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Asunto') }}</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Prioridad') }}</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Estado') }}</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">{{ __('Creado') }}</th>
        <th class="px-6 py-3 text-right text-xs font-medium text-white uppercase">{{ __('Acciones') }}</th>
      </tr>
    </thead>

    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
      @forelse($tickets as $t)
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
          <td class="px-6 py-4 text-gray-700 dark:text-gray-200">#{{ $t->id }}</td>

          <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
            {{ optional($t->cliente)->nombre ?? optional($t->usuario)->name ?? '—' }}
            @php $email = optional($t->cliente)->email ?? optional($t->usuario)->email; @endphp
            @if($email)
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ $email }}</div>
            @endif
          </td>

          <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
            {{ $t->asunto ?? $t->titulo ?? '—' }}
          </td>

          {{-- Prioridad --}}
          @php
            $prio = strtolower((string)($t->prioridad ?? ''));
            $prioMap = [
              'baja'    => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
              'media'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
              'alta'    => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
              'urgente' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            ];
            $prioLabel = $t->prioridad_label ?? ucfirst($prio ?: '—');
            $prioClass = $prioMap[$prio] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
          @endphp
          <td class="px-6 py-4">
            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $prioClass }}">
              {{ $prioLabel }}
            </span>
          </td>

          {{-- Estado --}}
          @php
            // Si tu BD usa "estado" string
            $est = strtolower((string)($t->estado ?? ''));

            // Si tu BD usa "status" numérico (0,1,2) y NO tienes "estado"
            if ($est === '' && isset($t->status)) {
              $mapNum = [0 => 'abierto', 1 => 'progreso', 2 => 'cerrado'];
              $est = $mapNum[(int)$t->status] ?? '';
            }

            $estMap = [
              'abierto'  => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
              'progreso' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
              'resuelto' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
              'cerrado'  => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
            ];

            $estLabel = $t->estado_label ?? ucfirst($est ?: '—');
            $estClass = $estMap[$est] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
          @endphp
          <td class="px-6 py-4">
            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $estClass }}">
              {{ $estLabel }}
            </span>
          </td>

          <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
            {{ optional($t->created_at)->format('Y-m-d H:i') ?? '—' }}
          </td>

          <td class="px-6 py-4 text-right space-x-2">
            <a href="{{ route('tickets.show', $t) }}"
               class="inline-flex items-center px-2 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-xs">
              {{ __('Ver') }}
            </a>

            {{-- ✅ En vez de Editar, mandamos a show para responder --}}
            <a href="{{ route('tickets.show', $t) }}"
               class="inline-flex items-center px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs">
              {{ __('Responder') }}
            </a>

            <form action="{{ route('tickets.destroy', $t) }}" method="POST" class="inline">
              @csrf
              @method('DELETE')
              <button type="button"
                      class="inline-flex items-center px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs btn-delete"
                      data-id="{{ $t->id }}">
                {{ __('Eliminar') }}
              </button>
            </form>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
            {{ __('No hay tickets registrados.') }}
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-right">
    {{ $tickets->appends([
        'search'    => $search ?? request('search'),
        'estado'    => $estado ?? request('estado'),
        'prioridad' => $prioridad ?? request('prioridad'),
      ])->links()
    }}
  </div>
</div>
