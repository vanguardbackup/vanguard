<div class="mt-4">
    @if ($remoteServers->isEmpty())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('hugeicons-cloud-server', 'h-16 w-16 text-primary-900 dark:text-white inline')
            </x-slot>
            <x-slot name="title">
                {{ __("You don't have any remote servers setup!") }}
            </x-slot>
            <x-slot name="description">
                {{ __('You can configure your first remote server by clicking the button below.') }}
            </x-slot>
            <x-slot name="action">
                <a href="{{ route('remote-servers.create') }}" wire:navigate>
                    <x-primary-button type="button" class="mt-4">
                        {{ __('Add Remote Server') }}
                    </x-primary-button>
                </a>
            </x-slot>
        </x-no-content>
    @else
        <x-table.table-wrapper
            title="{{ __('Remote Servers') }}"
            description="{{ __('A list of all linked remote servers, from which your data will be backed up.') }}"
        >
            <x-slot name="icon">
                <x-hugeicons-cloud-server class="h-6 w-6 text-primary-600 dark:text-primary-400" />
            </x-slot>
            <x-table.table-header>
                <div class="col-span-3">{{ __('Server Label') }}</div>
                <div class="col-span-3">{{ __('Host') }}</div>
                <div class="col-span-3">{{ __('Connection Status') }}</div>
                <div class="col-span-3">{{ __('Actions') }}</div>
            </x-table.table-header>
            <x-table.table-body>
                @foreach ($remoteServers as $remoteServer)
                    @livewire('remote-servers.index-item', ['remoteServer' => $remoteServer], key($remoteServer->id))
                @endforeach
            </x-table.table-body>
        </x-table.table-wrapper>
        <div class="mt-4 flex justify-end">
            {{ $remoteServers->links() }}
        </div>
    @endif
</div>
