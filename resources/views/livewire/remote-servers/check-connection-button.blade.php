<div>
@if ($remoteServer->isChecking())
        <x-secondary-button iconOnly type="button" disabled class="cursor-not-allowed bg-opacity-50">
            @svg('heroicon-o-arrow-path', 'h-4 w-4 animate-spin')
            <span class="sr-only">{{ __('Checking Connection') }}</span>
        </x-secondary-button>
    @else
        <x-secondary-button iconOnly wire:click="checkConnection" type="button">
            @svg('heroicon-o-arrow-path', 'h-4 w-4')
            <span class="sr-only">{{ __('Check Connection') }}</span>
        </x-secondary-button>
@endif
</div>
