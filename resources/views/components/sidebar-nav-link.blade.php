@props(['active'])

@php
    $classes = ($active ?? false)
                ? 'block text-gray-900 dark:text-gray-100 font-medium transition duration-150 ease-in-out'
                : 'block text-gray-500 hover:text-gray-700 hover:font-medium dark:text-gray-300 dark:hover:text-gray-200 font-normal transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
