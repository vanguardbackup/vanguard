@props(['active'])

@php
    $classes = 'block rounded-md text-sm font-medium transition-colors duration-150 ease-in-out w-full ';
    $classes .= ($active ?? false)
        ? 'bg-white dark:bg-gray-700 text-gray-950 dark:text-gray-200 shadow-none'
        : 'text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-400';
    $classes .= ' py-2 px-3 lg:py-2.5 lg:px-3'; // Added padding here
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
