@props(['centered' => false, 'iconOnly' => false, 'loadingText' => 'Loading...', 'action' => ''])

<button
    {{ $attributes->merge([
        'type' => 'button',
        'class' => 'inline-flex items-center ' .
                   ($iconOnly ? 'px-3 py-2' : 'px-7 py-2.5') .
                   ' bg-gray-100/75 dark:bg-gray-800 dark:border-gray-600 border border-gray-400/25 rounded-[0.70rem] font-semibold text-sm text-gray-700 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-gray-800 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150' .
                   ($centered ? ' justify-center w-full' : ''),
        'wire:loading.attr' => 'disabled',
        'wire:loading.class' => 'opacity-50 cursor-not-allowed',
        'wire:target' => $action
    ]) }}
>
    @if ($action)
        <div wire:loading wire:target="{{ $action }}">
            <x-spinner class="mr-2 text-gray-700 dark:text-gray-100 h-4 w-4 inline"/>
            {{ __($loadingText) }}
        </div>
        <div wire:loading.remove wire:target="{{ $action }}">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</button>
