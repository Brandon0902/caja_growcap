@props([
  'title' => '',
  'value' => 0,
  'money' => false,
  'icon' => '📊',
  'currency' => '$',    // símbolo si money=true
  'decimals' => 2,      // decimales si money=true
])

@php
  $n = (float) ($value ?? 0);
  $isNegative = $n < 0;
  $abs = abs($n);
  $formatted = $money
      ? number_format($abs, $decimals)
      : number_format($abs, 0);
  $textColor = $isNegative ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white';
@endphp

<div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow flex items-center gap-3">
  <div class="text-3xl">{{ $icon }}</div>
  <div>
    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $title }}</div>
    <div class="text-2xl font-semibold {{ $textColor }}">
      @if($isNegative)-@endif
      @if($money){{ $currency }} @endif{{ $formatted }}
    </div>
  </div>
</div>
