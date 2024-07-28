@props([
    'iconClass' => 'h-6 w-6 text-primary-600 dark:text-primary-400'
])

<div class="bg-white dark:bg-gray-800/50 dark:border-gray-800/30 rounded-[0.70rem] overflow-hidden border sm:rounded-[0.70rem] shadow-none">
    @if (isset($title))
        <div class="px-6 py-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-primary-100 dark:bg-primary-800 rounded-full p-3 mr-4">
                    @if (isset($icon))
                        <x-dynamic-component :component="$icon" :class="$iconClass" />
                    @else
                        @svg('heroicon-o-server', ['class' => $iconClass])
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
        <div class="border-t border-gray-200 dark:border-gray-700">
            @endif

            <div class="{{ isset($title) ? 'px-6 py-4' : 'p-6' }}">
                {{ $slot }}
            </div>

            @if (isset($title))
        </div>
    @endif
</div>
