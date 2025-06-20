{{-- resources/views/components/validation-errors.blade.php --}}
@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded']) }}>
        <div class="font-semibold">
            {{ __('Whoops! Something went wrong.') }}
        </div>
        <ul class="mt-3 list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
