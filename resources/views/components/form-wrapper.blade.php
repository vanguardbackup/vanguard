@props([
    'iconClass' => 'h-6 w-6 text-primary-600 dark:text-primary-400',
])

<div
    class="overflow-hidden rounded-[0.70rem] border bg-white pb-4 shadow-none sm:rounded-[0.70rem] dark:border-gray-800/30 dark:bg-gray-800/50"
>
    @if (isset($title))
        <div class="px-6 py-5">
            <div class="flex items-center">
                <div class="mr-4 flex-shrink-0 rounded-full bg-primary-100 p-3 dark:bg-primary-800">
                    @if (isset($icon))
                        <x-dynamic-component :component="$icon" :class="$iconClass" />
                    @else
                        @svg('hugeicons-information-circle', ['class' => $iconClass])
                    @endif
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $title }}
                    </h3>
                    @if (isset($description))
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $description }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div
        class="{{ isset($title) ? 'border-t border-gray-200 px-6 py-5 dark:border-gray-700' : 'p-6' }} text-sm leading-relaxed text-gray-700 sm:text-base dark:text-gray-300"
    >
        {{ $slot }}
    </div>
</div>
