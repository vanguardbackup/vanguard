<div>
    <x-slot name="action">
        <a href="{{ route('tags.create') }}" wire:navigate>
            <x-primary-button x-data="" centered>
                {{ __('Make Tag') }}
            </x-primary-button>
        </a>
    </x-slot>
    @if ($tags->isEmpty())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('hugeicons-tags', 'inline h-16 w-16 text-primary-900 dark:text-white')
            </x-slot>
            <x-slot name="title">
                {{ __("You don't have any tags setup!") }}
            </x-slot>
            <x-slot name="description">
                {{ __('Tags are a great way to organize backup tasks! Create your first tag below.') }}
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
        <x-table.table-wrapper
            title="{{ __('Tags') }}"
            description="{{ __('All tags used to organize and identify your backup tasks.') }}"
        >
            <x-slot name="icon">
                <x-hugeicons-tags class="h-6 w-6 text-primary-600 dark:text-primary-400" />
            </x-slot>
            <x-table.table-header>
                <div class="col-span-3">{{ __('Label') }}</div>
                <div class="col-span-3">{{ __('Description') }}</div>
                <div class="col-span-3">{{ __('Created') }}</div>
                <div class="col-span-3">{{ __('Actions') }}</div>
            </x-table.table-header>
            <x-table.table-body>
                @foreach ($tags as $tag)
                    @livewire('tags.index-item', ['tag' => $tag], key($tag->id))
                @endforeach
            </x-table.table-body>
        </x-table.table-wrapper>
        <div class="mt-4 flex justify-end">
            {{ $tags->links() }}
        </div>
    @endif
</div>
