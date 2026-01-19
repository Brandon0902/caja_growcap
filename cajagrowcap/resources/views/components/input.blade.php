{{-- resources/views/components/input.blade.php --}}
@props([
  'type'  => 'text',
  'name',
  'value' => null,
])

@php
  // Valor por defecto: primero old(), luego el prop value
  $val = old($name, $value);
@endphp

@if($type === 'textarea')
    <textarea
      name="{{ $name }}"
      id="{{ $name }}"
      {{ $attributes->merge([
          'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                      focus:border-purple-500 focus:ring-purple-500 
                      dark:bg-gray-700 dark:border-gray-600 dark:text-white'
      ]) }}
    >{{ $val }}</textarea>

@elseif($type === 'select')
    <select
      name="{{ $name }}"
      id="{{ $name }}"
      {{ $attributes->merge([
          'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                      focus:border-purple-500 focus:ring-purple-500 
                      dark:bg-gray-700 dark:border-gray-600 dark:text-white'
      ]) }}
    >
      {{ $slot }}
    </select>

@else
    <input
      type="{{ $type }}"
      name="{{ $name }}"
      id="{{ $name }}"
      value="{{ $val }}"
      {{ $attributes->merge([
          'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                      focus:border-purple-500 focus:ring-purple-500 
                      dark:bg-gray-700 dark:border-gray-600 dark:text-white'
      ]) }}
    />
@endif
