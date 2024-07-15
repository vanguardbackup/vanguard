@props(['active'])

@php
    $classes = ($active ?? false)
                ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-white text-start text-base font-medium text-white bg-gray-900 focus:outline-none focus:text-white focus:bg-gray-800 focus:border-white transition duration-150 ease-in-out'
                : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-200 hover:text-gray-200 hover:bg-gray-700 hover:border-gray-100 focus:outline-none focus:text-gray-200 focus:bg-gray-800 focus:border-gray-100 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
