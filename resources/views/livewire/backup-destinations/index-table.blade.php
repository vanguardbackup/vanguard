<div class="mt-4">
    @if ($backupDestinations->isEmpty())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('hugeicons-folder-cloud', 'inline h-16 w-16 text-primary-900 dark:text-white')
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
        <x-table.table-wrapper
            title="{{ __('Backup Destinations') }}"
            description="{{ __('A summary of configured backup destinations, where your backups will reside.') }}"
        >
            <x-slot name="icon">
                <x-hugeicons-folder-cloud class="h-6 w-6 text-primary-600 dark:text-primary-400" />
            </x-slot>
            <x-table.table-header>
                <div class="col-span-3">{{ __('Label') }}</div>
                <div class="col-span-3">{{ __('Type') }}</div>
                <div class="col-span-3">{{ __('Connection Status') }}</div>
                <div class="col-span-3">{{ __('Actions') }}</div>
            </x-table.table-header>
            <x-table.table-body>
                @foreach ($backupDestinations as $backupDestination)
                    @livewire(
                    'backup-destinations.index-item', ['backupDestination' => $backupDestination], key($backupDestination->id)                    )
                @endforeach
            </x-table.table-body>
        </x-table.table-wrapper>
        <div class="mt-4 flex justify-end">
            {{ $backupDestinations->links() }}
        </div>
    @endif
</div>
