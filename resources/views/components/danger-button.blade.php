@props(['centered' => false, 'iconOnly' => false, 'loadingText' => 'Loading...', 'action' => ''])

<button
    {{ $attributes->merge([
        'type' => 'submit',
        'class' => 'inline-flex items-center ' .
                   ($iconOnly ? 'px-3.5 py-2' : 'px-7 py-2.5') .
                   ' bg-red-600 border border-transparent rounded-[0.70rem] font-semibold text-sm text-white hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150' .
                   ($centered ? ' justify-center w-full' : ''),
        'wire:loading.attr' => 'disabled',
        'wire:loading.class' => 'opacity-50 cursor-not-allowed',
        'wire:target' => $action
    ]) }}
>
    @if($action)
        <div wire:loading wire:target="{{ $action }}">
            <x-spinner class="mr-2 text-white h-4 w-4 inline"/>
            {{ __($loadingText) }}
        </div>
        <div wire:loading.remove wire:target="{{ $action }}">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</button>
