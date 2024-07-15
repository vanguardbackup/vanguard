<div>
    <x-table.table-row>
        <div class="col-span-12 sm:col-span-3 flex flex-col sm:flex-row sm:items-center">
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $remoteServer->label }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 sm:hidden">
                {{ $remoteServer->ip_address }}
            </p>
        </div>

        <div class="col-span-12 sm:col-span-3 mt-2 sm:mt-0 hidden sm:block">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-normal text-gray-800 dark:text-gray-100">
                {{ $remoteServer->ip_address }}
            </span>
        </div>

        <div class="col-span-12 sm:col-span-3 mt-2 sm:mt-0">
            @if ($remoteServer->isOnline())
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                    <div class="h-2 w-2 rounded-full bg-green-500 mr-1.5"></div>
                    {{ __('Online') }}
                </span>
            @elseif ($remoteServer->isOffline())
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                    <div class="h-2 w-2 rounded-full bg-red-500 mr-1.5"></div>
                    {{ __('Offline') }}
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                    <div class="h-2 w-2 rounded-full bg-purple-500 mr-1.5"></div>
                    {{ __('Checking') }}
                </span>
            @endif
        </div>

        <div class="col-span-12 sm:col-span-3 mt-4 sm:mt-0 flex justify-start sm:justify-center space-x-2">
            @livewire('remote-servers.check-connection-button', ['remoteServer' => $remoteServer], key($remoteServer->id))
            <a href="{{ route('remote-servers.edit', $remoteServer) }}" wire:navigate>
                <x-secondary-button iconOnly>
                    <span class="sr-only">{{ __('Update Remote Server') }}</span>
                    <x-heroicon-o-pencil-square class="w-4 h-4"/>
                </x-secondary-button>
            </a>
        </div>
    </x-table.table-row>
</div>
