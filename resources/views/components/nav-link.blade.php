@props([
    'active',
])

@php
    $classes =
        $active ?? false
            ? 'group relative flex h-16 items-center overflow-hidden text-sm font-semibold leading-5 text-white transition duration-300 ease-in-out focus:outline-none'
            : 'group relative flex h-16 items-center overflow-hidden text-sm font-semibold leading-5 text-gray-200 transition duration-300 ease-in-out hover:text-white focus:text-white focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <span class="relative z-10 flex h-full items-center px-3">
        {{ $slot }}
    </span>

    {{-- Active indicator --}}
    @if ($active ?? false)
        <span class="absolute bottom-0 left-0 h-0.5 w-full bg-white"></span>
    @endif

    {{-- Hover indicator --}}
    <span
        class="absolute bottom-0 left-0 h-0.5 w-full origin-left scale-x-0 transform bg-white transition-transform duration-300 ease-out group-hover:scale-x-100"
    ></span>

    {{-- Subtle glow effect --}}
    <span
        class="absolute inset-0 bg-white opacity-0 transition-opacity duration-300 ease-in-out group-hover:opacity-10"
    ></span>
</a>
