@props([
  'title' => 'Título',
  'value' => 0,
  'icon'  => '👀',              // puedes mandar emoji o HTML/SVG
  'color' => 'indigo',          // indigo | blue | cyan | teal | green | lime | amber | orange | rose | red | purple | fuchsia | slate
  'money' => false,
  'currency' => '$',
  'sub' => null,                // texto pequeño debajo del título (opcional)
  'footer' => null,             // texto pequeño a la derecha (opcional)
])

@php
  // mapa de colores para fondo/borde/icono (modo claro/oscuro)
  $c = [
    'indigo'  => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/30','ring'=>'ring-indigo-200 dark:ring-indigo-800','icon'=>'text-indigo-600 dark:text-indigo-300'],
    'blue'    => ['bg' => 'bg-blue-50 dark:bg-blue-900/30','ring'=>'ring-blue-200 dark:ring-blue-800','icon'=>'text-blue-600 dark:text-blue-300'],
    'cyan'    => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/30','ring'=>'ring-cyan-200 dark:ring-cyan-800','icon'=>'text-cyan-600 dark:text-cyan-300'],
    'teal'    => ['bg' => 'bg-teal-50 dark:bg-teal-900/30','ring'=>'ring-teal-200 dark:ring-teal-800','icon'=>'text-teal-600 dark:text-teal-300'],
    'green'   => ['bg' => 'bg-green-50 dark:bg-green-900/30','ring'=>'ring-green-200 dark:ring-green-800','icon'=>'text-green-600 dark:text-green-300'],
    'lime'    => ['bg' => 'bg-lime-50 dark:bg-lime-900/30','ring'=>'ring-lime-200 dark:ring-lime-800','icon'=>'text-lime-600 dark:text-lime-300'],
    'amber'   => ['bg' => 'bg-amber-50 dark:bg-amber-900/30','ring'=>'ring-amber-200 dark:ring-amber-800','icon'=>'text-amber-600 dark:text-amber-300'],
    'orange'  => ['bg' => 'bg-orange-50 dark:bg-orange-900/30','ring'=>'ring-orange-200 dark:ring-orange-800','icon'=>'text-orange-600 dark:text-orange-300'],
    'rose'    => ['bg' => 'bg-rose-50 dark:bg-rose-900/30','ring'=>'ring-rose-200 dark:ring-rose-800','icon'=>'text-rose-600 dark:text-rose-300'],
    'red'     => ['bg' => 'bg-red-50 dark:bg-red-900/30','ring'=>'ring-red-200 dark:ring-red-800','icon'=>'text-red-600 dark:text-red-300'],
    'purple'  => ['bg' => 'bg-purple-50 dark:bg-purple-900/30','ring'=>'ring-purple-200 dark:ring-purple-800','icon'=>'text-purple-600 dark:text-purple-300'],
    'fuchsia' => ['bg' => 'bg-fuchsia-50 dark:bg-fuchsia-900/30','ring'=>'ring-fuchsia-200 dark:ring-fuchsia-800','icon'=>'text-fuchsia-600 dark:text-fuchsia-300'],
    'slate'   => ['bg' => 'bg-slate-50 dark:bg-slate-900/30','ring'=>'ring-slate-200 dark:ring-slate-800','icon'=>'text-slate-600 dark:text-slate-300'],
  ][$color] ?? ['bg'=>'bg-slate-50 dark:bg-slate-900/30','ring'=>'ring-slate-200 dark:ring-slate-800','icon'=>'text-slate-600 dark:text-slate-300'];

  $formatted = $money
    ? ($currency . ' ' . number_format((float)$value, 2))
    : number_format((float)$value);
@endphp

<div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
  <div class="p-4">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $title }}</p>
        @if($sub)
          <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $sub }}</p>
        @endif
        <div class="mt-2 text-3xl font-semibold text-gray-800 dark:text-gray-100">
          {{ $formatted }}
        </div>
      </div>

      <div class="shrink-0">
        <div class="w-12 h-12 grid place-items-center rounded-full {{ $c['bg'] }} ring-1 {{ $c['ring'] }}">
          <span class="text-2xl {{ $c['icon'] }}">
            {!! e($icon) == $icon ? $icon : $icon !!}
          </span>
        </div>
      </div>
    </div>
  </div>

  @if($footer)
    <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
      {{ $footer }}
    </div>
  @endif
</div>
