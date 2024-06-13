<div class="grid gap-0 text-center grid-cols-10">
    <x-table.body-item class="col-span-2 hidden md:block">
        {{ $remoteServer->label }}
    </x-table.body-item>
    <x-table.body-item class="col-span-2 hidden md:block">
        {{ $remoteServer->ip_address }}
    </x-table.body-item>
    <x-table.body-item class="col-span-2 block">
        @if ($remoteServer->isOnline())
            <div class="h-3 w-3 rounded-full inline-flex mr-1 bg-green-600"></div>
        @elseif ($remoteServer->isOffline())
            <div class="h-3 w-3 rounded-full inline-flex mr-1 bg-red-700"></div>
        @else
            <div class="h-3 w-3 rounded-full inline-flex mr-1 bg-purple-600"></div>
        @endif
        {{ ucfirst($remoteServer->connectivity_status) }}
    </x-table.body-item>
    <x-table.body-item class="col-span-2">
        {{ $remoteServer->backupTasks->count() }}
    </x-table.body-item>
    <x-table.body-item class="col-span-2">
        <div class="flex justify-center space-x-2">
            @livewire('remote-servers.check-connection-button', ['remoteServer' => $remoteServer],
            key($remoteServer->id))
            <a href="{{ route('remote-servers.edit', $remoteServer) }}" wire:navigate>
                <x-secondary-button iconOnly>
                    <span class="sr-only">{{ __('Update Remote Server') }}</span>
                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                </x-secondary-button>
            </a>
        </div>
    </x-table.body-item>
</div>
