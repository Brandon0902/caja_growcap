<button type="{{ $type }}"
        {{ $attributes->merge([
            'class' => 'px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500'
        ]) }}>
    {{ $slot }}
</button>
