@props([
    'centered' => false,
    'iconOnly' => false,
    'fat' => false,
    'loading' => false,
    'loadingText' => 'Saving...',
    'action' => '',
    'noLivewire' => false
])

<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex items-center ' .
        ($iconOnly ? 'px-3.5 py-2' : ($fat ? 'px-8 py-4 text-lg' : 'px-7 py-2.5')) .
        ' bg-primary-900 dark:bg-white dark:hover:bg-gray-200 dark:text-gray-900 border border-transparent rounded-[0.70rem] font-semibold text-sm text-white hover:bg-primary-950 focus:bg-primary-950 dark:focus:bg-white active:bg-primary-950 dark:active:bg-white focus:outline-none focus:ring-2 focus:ring-primary-950 focus:ring-offset-2 transition ease-in-out duration-150' .
        ($centered ? ' justify-center w-full' : '')
]) }}>
    @if (!$noLivewire)
        <div wire:loading wire:target="{{ $action }}">
            <x-spinner class="mr-2 text-white dark:text-gray-900 h-4 w-4 inline"/>
            {{ __($loadingText) }}
        </div>
        <div wire:loading.remove wire:target="{{ $action }}">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</button>
