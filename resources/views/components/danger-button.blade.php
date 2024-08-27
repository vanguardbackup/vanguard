@props([
    'centered' => false,
    'iconOnly' => false,
    'loadingText' => 'Loading...',
    'action' => '',
    'dropdown' => false,
])

<div class="relative inline-block text-left" x-data="{ open: false }">
    <button
        {{
            $attributes->merge([
                'type' => 'button',
                'class' =>
                    'inline-flex items-center relative overflow-hidden ' .
                    ($iconOnly ? 'p-2.5' : 'px-5 py-2.5') .
                    ' bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 ' .
                    'border border-gray-300 dark:border-gray-600 ' .
                    'rounded-[0.7rem] ' .
                    'font-medium text-sm text-red-600 dark:text-red-400 ' .
                    'focus:outline-none focus:ring-2 focus:ring-red-500 dark:focus:ring-red-400 ' .
                    'focus:ring-offset-2 dark:focus:ring-offset-gray-900 ' .
                    'disabled:opacity-50 disabled:cursor-not-allowed ' .
                    'transition-all duration-300 ease-in-out ' .
                    'shadow-sm hover:shadow-md ' .
                    'group ' .
                    ($centered ? 'justify-center w-full' : ''),
                'wire:loading.attr' => 'disabled',
                'wire:loading.class' => 'opacity-50 cursor-not-allowed',
                'wire:target' => $action,
                '@click' => $dropdown ? 'open = !open' : '',
            ])
        }}
    >
        <span
            class="relative z-10 flex items-center transition-colors duration-300 ease-in-out group-hover:text-white group-active:text-white"
        >
            @if ($action)
                <div wire:loading wire:target="{{ $action }}" class="flex items-center">
                    <x-spinner class="mr-2 h-4 w-4 text-red-500 group-hover:text-white dark:text-red-400" />
                    <span>{{ __($loadingText) }}</span>
                </div>
                <div wire:loading.remove wire:target="{{ $action }}">
                    {{ $slot }}
                </div>
            @else
                {{ $slot }}
            @endif

            @if ($dropdown)
                <svg
                    class="ml-2 h-5 w-5 text-red-600 group-hover:text-white group-active:text-white dark:text-red-400"
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
            @endif
        </span>
        <span
            class="absolute inset-0 origin-left scale-x-0 transform bg-gradient-to-r from-red-600 to-red-500 transition-transform duration-300 ease-out group-hover:scale-x-100 group-active:scale-x-100 dark:from-red-700 dark:to-red-600"
        ></span>
    </button>

    @if ($dropdown)
        <div
            x-show="open"
            @click.away="open = false"
            x-transition:enter="transition duration-100 ease-out"
            x-transition:enter-start="scale-95 transform opacity-0"
            x-transition:enter-end="scale-100 transform opacity-100"
            x-transition:leave="transition duration-75 ease-in"
            x-transition:leave-start="scale-100 transform opacity-100"
            x-transition:leave-end="scale-95 transform opacity-0"
            class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-[0.70rem] bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800"
            role="menu"
            aria-orientation="vertical"
            aria-labelledby="menu-button"
            tabindex="-1"
        >
            <div class="py-1" role="none">
                {{ $dropdownContent }}
            </div>
        </div>
    @endif
</div>
