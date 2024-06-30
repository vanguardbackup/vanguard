@props(['title' => null])
<div
    class="mt-6 overflow-hidden border border-gray-950/5 dark:border-gray-800/30 bg-white dark:bg-gray-800/50 sm:rounded-[0.70rem] pt-4">
    @isset($title)
        <div class="px-4 sm:px-6">
            <div class="md:flex justify-between">
                <div>
                    @isset($icon)
                        {{ $icon }}
                    @endisset
                    <h1 class="inline text-lg font-medium leading-6 text-gray-900 dark:text-white">
                        {{ $title }}
                    </h1>
                    <p class="dark:text-gray-200 text-gray-600 font-medium text-sm text-center sm:text-left sm:ml-8">
                        {{ $description ?? '' }}
                    </p>
                </div>
                <div class="hidden sm:block dark:text-gray-100 text-gray-500 text-sm">
                    {{-- i.e. things like a search bar or a button maybe! --}}
                    {{ $headerContent ?? '' }}
                </div>
            </div>
        </div>
    @endisset
    <div>
        @isset($header)
            <div class="mt-3 border-t border-gray-200 dark:border-gray-700/30"></div>
            <div {{ $attributes->merge(['class' => 'grid gap-0 text-center bg-gray-50 px-2 py-1.5 border-gray-200 dark:border-gray-700/30 font-medium text-gray-800 dark:text-gray-100 dark:bg-gray-900/30']) }} >
                {{ $header }}
            </div>
        @endisset

        @isset($advancedBody)
            <div class="border-t border-gray-200 dark:border-gray-700/30 {{ isset($header) ? 'mt-0' : 'mt-4' }}"></div>
            {{ $advancedBody }}
        @endisset

        @isset($body)
            <div class="border-t border-gray-200 dark:border-gray-700/30 {{ isset($header) ? 'mt-0' : 'mt-4' }}"></div>
            <div {{ $attributes->merge(['class' => 'grid gap-0 text-center']) }}>
                {{ $body }}
            </div>
        @endisset
    </div>
</div>
