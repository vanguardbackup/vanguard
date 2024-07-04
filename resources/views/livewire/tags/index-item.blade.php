<div class="grid gap-0 text-center grid-cols-8">
    <x-table.body-item class="col-span-2 hidden md:block">
        {{ $tag->label }}
    </x-table.body-item>
    <x-table.body-item class="col-span-2 hidden md:block">
        {{ $tag->description ?? __('—') }}
    </x-table.body-item>
    <x-table.body-item class="col-span-2 hidden md:block">
        {{ ucfirst($tag->created_at->timezone(auth()->user()->timezone)->locale(auth()->user()->language ?? app()->getLocale())->isoFormat('MMMM D, YYYY [at] h:mm A')) }}
    </x-table.body-item>
    <x-table.body-item class="col-span-2">
        <div class="flex justify-center space-x-2">
            <a href="{{ route('tags.edit', $tag) }}" wire:navigate>
                <x-secondary-button iconOnly>
                    <span class="sr-only">{{ __('Update Tag') }}</span>
                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                </x-secondary-button>
            </a>
        </div>
    </x-table.body-item>
</div>
