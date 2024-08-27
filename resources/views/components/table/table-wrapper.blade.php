<div
    class="overflow-hidden rounded-[0.70rem] border bg-white shadow-none sm:rounded-[0.70rem] dark:border-gray-800/30 dark:bg-gray-800/50"
>
    <div class="px-4 py-5 sm:px-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="mb-4 flex items-center sm:mb-0">
                <div class="mr-4 flex-shrink-0 rounded-full bg-primary-100 p-3 dark:bg-primary-800">
                    {{ $icon }}
                </div>
                <div>
                    <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-white">
                        {{ $title }}
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                        {{ $description }}
                    </p>
                </div>
            </div>
            @isset($action)
                <div class="flex-shrink-0">
                    {{ $action }}
                </div>
            @endisset
        </div>
    </div>
    <div class="border-t border-gray-200 px-4 py-5 sm:px-6 dark:border-gray-700">
        {{ $slot }}
    </div>
</div>
