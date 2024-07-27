@props([
    'centered' => false,
    'iconOnly' => false,
    'loadingText' => 'Loading...',
    'action' => '',
])

<button
    {{ $attributes->merge([
        'type' => 'button',
        'class' => 'inline-flex items-center ' .
                   ($iconOnly ? 'p-2.5' : 'px-5 py-2.5') .
                   ' bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 ' .
                   'border border-gray-300 dark:border-gray-600 ' .
                   'rounded-[0.7rem] ' .
                   'font-medium text-sm text-gray-700 dark:text-gray-200 ' .
                   'hover:from-gray-100 hover:to-gray-200 dark:hover:from-gray-700 dark:hover:to-gray-600 ' .
                   'focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 ' .
                   'focus:ring-offset-2 dark:focus:ring-offset-gray-900 ' .
                   'active:from-gray-200 active:to-gray-300 dark:active:from-gray-600 dark:active:to-gray-500 ' .
                   'disabled:opacity-50 disabled:cursor-not-allowed ' .
                   'transition-all duration-200 ease-in-out ' .
                   'shadow-sm hover:shadow-md ' .
                   ($centered ? 'justify-center w-full' : ''),
        'wire:loading.attr' => 'disabled',
        'wire:loading.class' => 'opacity-50 cursor-not-allowed',
        'wire:target' => $action
    ]) }}
>
    @if ($action)
        <div wire:loading wire:target="{{ $action }}" class="flex items-center">
            <x-spinner class="mr-2 text-primary-500 dark:text-primary-400 h-4 w-4"/>
            <span>{{ __($loadingText) }}</span>
        </div>
        <div wire:loading.remove wire:target="{{ $action }}">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</button>
