@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'bg-white py-1 dark:bg-gray-800'])

@php
    $alignmentClasses = match ($align) {
        'left' => 'start-0 ltr:origin-top-left rtl:origin-top-right',
        'top' => 'origin-top',
        'right' => 'end-0 ltr:origin-top-right rtl:origin-top-left',
        default => 'end-0 ltr:origin-top-right rtl:origin-top-left',
    };

    $width = match ($width) {
        '48' => 'w-48',
        default => $width,
    };
@endphp

<div class="relative" x-data="{ open: false }" @click.away="open = false" @close.stop="open = false">
    <div @click="open = !open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-transition:enter="transition duration-300 ease-out"
        x-transition:enter-start="translate-y-2 scale-95 transform opacity-0"
        x-transition:enter-end="translate-y-0 scale-100 transform opacity-100"
        x-transition:leave="transition duration-200 ease-in"
        x-transition:leave-start="translate-y-0 scale-100 transform opacity-100"
        x-transition:leave-end="translate-y-2 scale-95 transform opacity-0"
        class="{{ $width }} {{ $alignmentClasses }} absolute z-50 mt-2 rounded-md shadow-lg"
        style="display: none"
        @click="open = false"
    >
        <div class="{{ $contentClasses }} overflow-hidden rounded-md ring-1 ring-black ring-opacity-5">
            {{ $content }}
        </div>
    </div>
</div>
