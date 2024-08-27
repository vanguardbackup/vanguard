@props([
    'centered' => false,
    'iconOnly' => false,
    'loadingText' => 'Loading...',
    'action' => '',
])

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
        ])
    }}
>
    <span class="relative z-10 transition-colors duration-300 ease-in-out group-hover:text-white">
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
    </span>
    <span
        class="absolute inset-0 origin-left scale-x-0 transform bg-gradient-to-r from-red-600 to-red-500 transition-transform duration-300 ease-out group-hover:scale-x-100 dark:from-red-700 dark:to-red-600"
    ></span>
</button>
