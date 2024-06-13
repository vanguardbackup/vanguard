<div class="mt-4">
    @if ($backupDestinations->isEmpty())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('heroicon-o-globe-alt', 'h-16 w-16 text-primary-900 dark:text-white inline')
            </x-slot>
            <x-slot name="title">
                {{ __("You don't have any backup destinations!") }}
            </x-slot>
            <x-slot name="description">
                {{ __('You can configure your first backup destination by clicking the button below.') }}
            </x-slot>
            <x-slot name="action">
                <a href="{{ route('backup-destinations.create') }}" wire:navigate>
                    <x-primary-button type="button" class="mt-4">
                        {{ __('Add Backup Destination') }}
                    </x-primary-button>
                </a>
            </x-slot>
        </x-no-content>
    @else
        <x-table.wrapper title="{{ __('Backup Destinations') }}" class="grid-cols-8">
            <x-slot name="header">
                <x-table.header-item class="col-span-2">
                    {{ __('Label') }}
                </x-table.header-item>
                <x-table.header-item class="col-span-2">
                    {{ __('Type') }}
                </x-table.header-item>
                <x-table.header-item class="col-span-2">
                    {{ __('Connection Status') }}
                </x-table.header-item>
                <x-table.header-item class="col-span-2">
                    {{ __('Actions') }}
                </x-table.header-item>
            </x-slot>
            <x-slot name="advancedBody">
                @foreach ($backupDestinations as $backupDestination)
                    @livewire('backup-destinations.index-item', ['backupDestination' => $backupDestination], key($backupDestination->id))
                @endforeach
            </x-slot>
        </x-table.wrapper>
        <div class="mt-4 flex justify-end">
            {{ $backupDestinations->links() }}
        </div>
    @endif
</div>
