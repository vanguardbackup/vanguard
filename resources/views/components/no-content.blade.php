@props([
    'icon',
    'title',
    'description',
    'action' => null,
    'withBackground' => false,
])

@if ($withBackground)
    <div
        class="overflow-hidden rounded-[0.70rem] border border-gray-200 bg-white p-8 shadow-none transition duration-300 ease-in-out hover:shadow-md dark:border-gray-800/30 dark:bg-gray-800/50"
    >
        <div class="my-10 text-center">
            <div class="mb-4 flex justify-center">
                <div
                    class="inline-flex h-28 w-28 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-800"
                >
                    {{ $icon }}
                </div>
            </div>
            <h3 class="my-4 text-xl font-semibold text-gray-900 dark:text-white">
                {{ $title }}
            </h3>
            <p class="text-lg font-medium text-gray-700 dark:text-gray-300">
                {{ $description }}
            </p>
            @isset($action)
                <div class="mt-6">
                    {{ $action }}
                </div>
            @endisset
        </div>
    </div>
@else
    <div class="my-10 text-center">
        <div class="mb-4 flex justify-center">
            <div
                class="inline-flex h-28 w-28 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-800"
            >
                {{ $icon }}
            </div>
        </div>
        <h3 class="my-4 text-xl font-semibold text-gray-900 dark:text-white">
            {{ $title }}
        </h3>
        <p class="text-lg font-medium text-gray-700 dark:text-gray-300">
            {{ $description }}
        </p>
        @isset($action)
            <div class="mt-6">
                {{ $action }}
            </div>
        @endisset
    </div>
@endif
