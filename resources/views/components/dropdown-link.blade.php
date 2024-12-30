@props(['active' => false])

@php
    $classes =
        'block w-full px-4 py-2 text-start text-sm font-normal leading-5 transition duration-150 ease-in-out cursor-pointer rounded ';
    $classes .= $active
        ? 'bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-100 shadow-sm rounded'
        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 rounded';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
