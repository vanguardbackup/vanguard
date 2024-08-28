@props([
    'centered' => false,
    'iconOnly' => false,
    'fat' => false,
    'loading' => false,
    'loadingText' => 'Saving...',
    'action' => '',
    'noLivewire' => false,
])

<div @class([
    'relative inline-flex',
    'w-full' => $centered,
]) x-data="{ open: false }">
    <button
        {{
            $attributes->merge([
                'type' => 'button',
                'class' =>
                    'inline-flex items-center flex-grow ' .
                    ($iconOnly ? 'px-3.5 py-2' : ($fat ? 'px-8 py-4 text-lg' : 'px-7 py-2.5')) .
                    ' bg-primary-900 dark:bg-white dark:hover:bg-gray-200 dark:text-gray-900 border border-transparent ' .
                    'rounded-l-[0.70rem] font-semibold text-sm text-white hover:bg-primary-950 focus:bg-primary-950 ' .
                    'dark:focus:bg-white active:bg-primary-950 dark:active:bg-white focus:outline-none focus:ring-2 ' .
                    'focus:ring-primary-950 focus:ring-offset-2 transition ease-in-out duration-150' .
                    ($centered ? ' justify-center' : ''),
            ])
        }}
    >
        @if (! $noLivewire)
            <div wire:loading wire:target="{{ $action }}">
                <x-spinner class="mr-2 inline h-4 w-4 text-white dark:text-gray-900" />
                {{ __($loadingText) }}
            </div>
            <div wire:loading.remove wire:target="{{ $action }}">
                {{ $slot }}
            </div>
        @else
            {{ $slot }}
        @endif
    </button>

    <button
        type="button"
        @click="open = !open"
        aria-haspopup="true"
        :aria-expanded="open"
        class="inline-flex items-center rounded-r-[0.70rem] border-l border-primary-800 bg-primary-900 px-3 py-2 text-sm text-white transition duration-150 ease-in-out hover:bg-primary-950 focus:bg-primary-950 focus:outline-none focus:ring-2 focus:ring-primary-950 focus:ring-offset-2 active:bg-primary-950 dark:border-gray-300 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200 dark:focus:bg-white dark:active:bg-white"
    >
        <svg
            class="h-5 w-5"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
        >
            <path
                fill-rule="evenodd"
                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                clip-rule="evenodd"
            />
        </svg>
    </button>

    <div
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition duration-100 ease-out"
        x-transition:enter-start="scale-95 transform opacity-0"
        x-transition:enter-end="scale-100 transform opacity-100"
        x-transition:leave="transition duration-75 ease-in"
        x-transition:leave-start="scale-100 transform opacity-100"
        x-transition:leave-end="scale-95 transform opacity-0"
        class="absolute right-0 z-10 mt-2 w-56 divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:divide-gray-700 dark:bg-gray-800"
        style="top: 100%"
        role="menu"
        aria-orientation="vertical"
        tabindex="-1"
    >
        <div class="py-1" role="none">
            {{ $dropdownContent }}
        </div>
    </div>
</div>
