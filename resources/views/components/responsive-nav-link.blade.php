@props([
    'active',
])

@php
    $classes = 'block w-full px-4 py-2 text-base font-medium transition duration-200 ease-in-out ';
    $classes .=
        $active ?? false
            ? 'text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white'
            : 'text-gray-300 hover:bg-gray-700 hover:text-white focus:outline-none focus:bg-gray-700 focus:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <div class="flex items-center">
        @if ($active ?? false)
            <span class="absolute inset-y-0 left-0 w-1 rounded-r-full bg-white" aria-hidden="true"></span>
        @endif

        <span class="relative">{{ $slot }}</span>
    </div>
</a>
