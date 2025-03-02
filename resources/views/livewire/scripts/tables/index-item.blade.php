<div>
    <x-table.table-row>
        <div class="col-span-12 flex flex-col sm:col-span-3 sm:flex-row sm:items-center">
            <p class="font-medium text-gray-900 dark:text-gray-100">
                {{ $script->label }}
            </p>
        </div>

        <div class="col-span-12 mt-2 sm:col-span-3 sm:mt-0">
            <span class="block truncate text-sm text-gray-800 dark:text-gray-200">
                {{ ucfirst($script->type) }}
            </span>
        </div>

        <div class="col-span-12 mt-2 sm:col-span-3 sm:mt-0">
            <span class="inline-flex items-center text-sm text-gray-800 dark:text-gray-100">
                {{ $script->created_at->timezone(auth()->user()->timezone)->locale(auth()->user()->language ?? app()->getLocale())->isoFormat('MMMM D, YYYY') }}
            </span>
        </div>

        <div class="col-span-12 mt-4 flex justify-start space-x-2 sm:col-span-3 sm:mt-0 sm:justify-center">
            @livewire('scripts.delete-script-button', ['script' => $script])
        </div>
    </x-table.table-row>
</div>
