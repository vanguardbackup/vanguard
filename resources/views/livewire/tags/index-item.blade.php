<div>
    <x-table.table-row>
        <div class="col-span-12 sm:col-span-3 flex flex-col sm:flex-row sm:items-center">
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $tag->label }}</p>
        </div>

        <div class="col-span-12 sm:col-span-3 mt-2 sm:mt-0">
            <span class="text-xs text-gray-800 dark:text-gray-200 truncate">
                {{ $tag->description ?? __('—') }}
            </span>
        </div>

        <div class="col-span-12 sm:col-span-3 mt-2 sm:mt-0">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                {{ $tag->created_at->timezone(auth()->user()->timezone)->locale(auth()->user()->language ?? app()->getLocale())->isoFormat('MMMM D, YYYY') }}
            </span>
        </div>

        <div class="col-span-12 sm:col-span-3 mt-4 sm:mt-0 flex justify-start sm:justify-center space-x-2">
            <a href="{{ route('tags.edit', $tag) }}" wire:navigate>
                <x-secondary-button iconOnly>
                    <span class="sr-only">{{ __('Update Tag') }}</span>
                    <x-heroicon-o-pencil-square class="w-4 h-4"/>
                </x-secondary-button>
            </a>
        </div>
    </x-table.table-row>
</div>
