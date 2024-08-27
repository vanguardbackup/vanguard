@props([
    'value',
])

<label
    {{ $attributes->merge(['class' => 'mb-1 block font-medium text-sm text-gray-900/80 dark:text-gray-300']) }}
>
    {{ $value ?? $slot }}
</label>
