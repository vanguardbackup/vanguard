<div class="mt-4">
    @if ($tags->isEmpty())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('heroicon-o-tag', 'h-16 w-16 text-primary-900 dark:text-white inline')
            </x-slot>
            <x-slot name="title">
                {{ __("You don't have any tags setup!") }}
            </x-slot>
            <x-slot name="description">
                {{ __("Tags are a great way to organize backup tasks! create your first tag below.") }}
            </x-slot>
            <x-slot name="action">
                <a href="{{ route('tags.create') }}" wire:navigate>
                    <x-primary-button type="button" class="mt-4">
                        {{ __('Make Tag') }}
                    </x-primary-button>
                </a>
            </x-slot>
        </x-no-content>
    @else
        <x-table.wrapper title="{{ __('Tags') }}" class="grid-cols-8">
            <x-slot name="icon">
                @svg('heroicon-o-tag', 'h-6 w-6 text-gray-800 dark:text-gray-200 mr-1.5 inline')
            </x-slot>
            <x-slot name="description">
                {{ __('All tags used to organize and identify your backup tasks.') }}
            </x-slot>
            <x-slot name="header">
                <x-table.header-item class="col-span-2">
                    {{ __('Label') }}
                </x-table.header-item>
                <x-table.header-item class="col-span-2">
                    {{ __('Description') }}
                </x-table.header-item>
                <x-table.header-item class="col-span-2">
                    {{ __('Created') }}
                </x-table.header-item>
                <x-table.header-item class="col-span-2">
                    {{ __('Actions') }}
                </x-table.header-item>
            </x-slot>
            <x-slot name="advancedBody">
                @foreach ($tags as $tag)
                    @livewire('tags.index-item', ['tag' => $tag], key($tag->id))
                @endforeach
            </x-slot>
        </x-table.wrapper>
        <div class="mt-4 flex justify-end">
            {{ $tags->links() }}
        </div>
    @endif
</div>
