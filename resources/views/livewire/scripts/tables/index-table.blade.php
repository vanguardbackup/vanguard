<div>
    <x-slot name="action">
        <a href="{{ route('scripts.create') }}" wire:navigate>
            <x-primary-button x-data="" centered>
                {{ __('Make Script') }}
            </x-primary-button>
        </a>
    </x-slot>
    @if ($scripts->isEmpty())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('hugeicons-computer', 'inline h-16 w-16 text-primary-900 dark:text-white')
            </x-slot>
            <x-slot name="title">
                {{ __("You don't have any scripts setup!") }}
            </x-slot>
            <x-slot name="description">
                {{ __('Scripts are great for executing code on your remote server before or after a backup task.') }}
            </x-slot>
            <x-slot name="action">
                <a href="{{ route('scripts.create') }}" wire:navigate>
                    <x-primary-button type="button" class="mt-4">
                        {{ __('Make Script') }}
                    </x-primary-button>
                </a>
            </x-slot>
        </x-no-content>
    @else
        <x-table.table-wrapper
            title="{{ __('Scripts') }}"
            description="{{ __('Scripts can run before or after your backup task starts.') }}"
        >
            <x-slot name="icon">
                <x-hugeicons-computer class="h-6 w-6 text-primary-600 dark:text-primary-400" />
            </x-slot>
            <x-table.table-header>
                <div class="col-span-3">{{ __('Label') }}</div>
                <div class="col-span-3">{{ __('Status') }}</div>
                <div class="col-span-3">{{ __('Type') }}</div>
                <div class="col-span-3">{{ __('Actions') }}</div>
            </x-table.table-header>
            <x-table.table-body>
                @foreach ($scripts as $script)
                    @livewire('scripts.tables.index-item', ['script' => $script], key($script->id))
                @endforeach
            </x-table.table-body>
        </x-table.table-wrapper>
        <div class="mt-4 flex justify-end">
            {{ $scripts->links() }}
        </div>
    @endif
</div>
