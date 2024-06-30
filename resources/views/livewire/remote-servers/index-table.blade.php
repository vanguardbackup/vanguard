<div class="mt-4">
    @if ($remoteServers->isEmpty())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('heroicon-o-server', 'h-16 w-16 text-primary-900 dark:text-white inline')
            </x-slot>
            <x-slot name="title">
                {{ __("You don't have any remote servers setup!") }}
            </x-slot>
            <x-slot name="description">
                {{ __("You can configure your first remote server by clicking the button below.") }}
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
        <x-table.wrapper title="{{ __('Remote Servers') }}" class="grid-cols-10">
            <x-slot name="icon">
                @svg('heroicon-o-server-stack', 'h-6 w-6 text-gray-800 dark:text-gray-200 mr-1.5 inline')
            </x-slot>
            <x-slot name="description">
                {{ __('A list of all linked remote servers, from which your data will be backed up.') }}
            </x-slot>
            <x-slot name="header">
                <x-table.header-item class="col-span-2">
                    {{ __('Server Label') }}
                </x-table.header-item>
                <x-table.header-item class="col-span-2">
                    {{ __('Host') }}
                </x-table.header-item>
                <x-table.header-item class="col-span-2">
                    {{ __('Connection Status') }}
                </x-table.header-item>
                <x-table.header-item class="col-span-2">
                    {{ __('Backup Tasks Configured') }}
                </x-table.header-item>
                <x-table.header-item class="col-span-2">
                    {{ __('Actions') }}
                </x-table.header-item>
            </x-slot>
            <x-slot name="advancedBody">
                @foreach ($remoteServers as $remoteServer)
                    @livewire('remote-servers.index-item', ['remoteServer' => $remoteServer], key($remoteServer->id))
                @endforeach
            </x-slot>
        </x-table.wrapper>
        <div class="mt-4 flex justify-end">
            {{ $remoteServers->links() }}
        </div>
    @endif
</div>
