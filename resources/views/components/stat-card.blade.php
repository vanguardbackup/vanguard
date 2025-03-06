@props([
    'icon' => 'hugeicons-information-circle',
    'title',
    'description',
])

<div
    {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800/50 dark:border-gray-800/30 rounded-[0.70rem] overflow-hidden border sm:rounded-[0.70rem] shadow-none transition duration-300 ease-in-out hover:shadow-md']) }}
>
    <div class="px-6 py-5">
        <div class="flex items-center">
            <div class="mr-4 flex-shrink-0 rounded-full bg-primary-100 p-3 dark:bg-primary-800">
                @svg($icon, ['class' => 'h-6 w-6 text-primary-600 dark:text-primary-400'])
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $title }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $description }}
                </p>
            </div>
        </div>
    </div>
    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
        {{ $slot }}
    </div>
</div>
