<div>
    <x-table.table-row>
        <div class="col-span-12 flex flex-col sm:col-span-3 sm:flex-row sm:items-center">
            <p class="font-medium text-gray-900 dark:text-gray-100">
                {{ $tag->label }}
            </p>
        </div>

        <div class="col-span-12 mt-2 sm:col-span-3 sm:mt-0">
            <span class="block truncate text-sm text-gray-800 dark:text-gray-200">
                {{ Str::limit($tag->description ?? '—', 50) }}
            </span>
        </div>

        <div class="col-span-12 mt-2 sm:col-span-3 sm:mt-0">
            <span class="inline-flex items-center text-sm text-gray-800 dark:text-gray-100">
                {{ $tag->created_at->timezone(auth()->user()->timezone)->locale(auth()->user()->language ?? app()->getLocale())->isoFormat('MMMM D, YYYY') }}
            </span>
        </div>

        <div class="col-span-12 mt-4 flex justify-start space-x-2 sm:col-span-3 sm:mt-0 sm:justify-center">
            <a href="{{ route('tags.edit', $tag) }}" wire:navigate>
                <x-secondary-button iconOnly>
                    <span class="sr-only">{{ __('Update Tag') }}</span>
                    <x-hugeicons-task-edit-01 class="h-4 w-4" />
                </x-secondary-button>
            </a>
        </div>
    </x-table.table-row>
</div>
