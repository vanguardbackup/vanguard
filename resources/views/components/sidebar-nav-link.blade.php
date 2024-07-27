@props(['active'])

@php
    $classes = 'block rounded text-xs lg:text-sm font-medium transition-all duration-200 ease-in-out w-full ';
    $classes .= ($active ?? false)
        ? 'bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-100 shadow-sm'
        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100';
    $classes .= ' px-1 py-1 lg:px-2 lg:py-1.5';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
