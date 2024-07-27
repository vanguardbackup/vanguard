@props(['active'])

@php
    $classes = ($active ?? false)
                ? 'flex items-center h-16 text-sm font-semibold leading-5 text-white focus:outline-none transition duration-300 ease-in-out relative group overflow-hidden'
                : 'flex items-center h-16 text-sm font-semibold leading-5 text-gray-200 hover:text-white focus:outline-none focus:text-white transition duration-300 ease-in-out relative group overflow-hidden';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <span class="flex items-center px-3 h-full relative z-10">
        {{ $slot }}
    </span>

    {{-- Active indicator --}}
    @if($active ?? false)
        <span class="absolute bottom-0 left-0 w-full h-0.5 bg-white"></span>
    @endif

    {{-- Hover indicator --}}
    <span class="absolute bottom-0 left-0 w-full h-0.5 bg-white transform scale-x-0 origin-left transition-transform duration-300 ease-out group-hover:scale-x-100"></span>

    {{-- Subtle glow effect --}}
    <span class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300 ease-in-out"></span>
</a>
