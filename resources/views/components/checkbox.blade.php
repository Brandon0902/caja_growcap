@props(['name', 'checked' => false])

{{-- Siempre enviar 0 si el checkbox no está marcado --}}
<input type="hidden" name="{{ $name }}" value="0">

<input
    type="checkbox"
    name="{{ $name }}"
    id="{{ $name }}"
    value="1"
    {{ old($name, $checked) ? 'checked' : '' }}
    {{ $attributes->merge([
        'class' => 'h-4 w-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600'
    ]) }}
/>
