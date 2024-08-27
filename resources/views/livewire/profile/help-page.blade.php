<div>
    @section('title', __('Get Help'))

    <x-slot name="header">
        {{ __('Get Help') }}
    </x-slot>

    <x-form-wrapper>
        <x-slot name="icon">hugeicons-mentoring</x-slot>

        <x-slot name="title">
            {{ __('Get Help') }}
        </x-slot>

        <x-slot name="description">
            {{ __('If you are a bit stuck and need some support, this should help you receive it!') }}
        </x-slot>

        <div class="space-y-6 sm:space-y-8">
            <p>
                {{ __('Vanguard offers various resources to help you get the most out of our backup solution. Here are some quick links to get you started:') }}
            </p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                @php
                    $resources = [
                        ['title' => 'Documentation', 'icon' => 'hugeicons-book-open-01', 'url' => 'https://docs.vanguardbackup.com', 'description' => 'Comprehensive guides and API references'],
                        ['title' => 'GitHub Discussions', 'icon' => 'hugeicons-chatting-01', 'url' => 'https://github.com/vanguardbackup/vanguard/discussions', 'description' => 'Community-driven Q&A and discussions'],
                        ['title' => 'Vanguard Website', 'icon' => 'hugeicons-browser', 'url' => 'https://vanguardbackup.com', 'description' => 'Product information and latest updates'],
                    ];
                @endphp

                @foreach ($resources as $resource)
                    <a href="{{ $resource['url'] }}" class="group block" target="_blank" rel="noopener noreferrer">
                        <div
                            class="flex h-full flex-col rounded-lg border border-gray-200 bg-gray-50 p-4 transition duration-300 ease-in-out hover:bg-gray-100 hover:shadow-md sm:p-6 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700"
                        >
                            <div class="mb-3 flex items-center sm:mb-4">
                                <x-dynamic-component
                                    :component="$resource['icon']"
                                    class="h-5 w-5 transform text-gray-600 transition-transform duration-300 ease-in-out group-hover:scale-110 sm:h-6 sm:w-6 dark:text-gray-400"
                                />
                                <h3
                                    class="ml-2 text-base font-semibold text-gray-900 sm:ml-3 sm:text-lg dark:text-white"
                                >
                                    {{ __($resource['title']) }}
                                </h3>
                            </div>
                            <p class="flex-grow text-xs text-gray-600 sm:text-sm dark:text-gray-400">
                                {{ __($resource['description']) }}
                            </p>
                            <div
                                class="mt-3 flex items-center text-xs font-medium text-gray-900 underline sm:mt-4 sm:text-sm dark:text-white"
                            >
                                {{ __('Learn more') }}
                                <x-hugeicons-arrow-right-02
                                    class="ml-1 h-3 w-3 transform transition-transform duration-300 ease-in-out group-hover:translate-x-1 sm:h-4 sm:w-4"
                                />
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-3 text-lg font-semibold text-gray-900 sm:mb-4 sm:text-xl dark:text-white">
                    {{ __('Need Further Assistance?') }}
                </h3>
                <p class="mb-4 text-sm text-gray-700 sm:text-base dark:text-gray-300">
                    {{ __('If you couldn\'t find what you\'re looking for in the resources above, we can help:') }}
                </p>
                <ul class="space-y-3 text-sm text-gray-700 sm:text-base dark:text-gray-300">
                    <li class="flex flex-wrap items-center">
                        <x-hugeicons-mail-01 class="mr-2 h-5 w-5 text-gray-600 sm:mr-3 dark:text-gray-400" />
                        <span class="font-medium">{{ __('Support:') }}</span>
                        <a
                            href="mailto:support@vanguardbackup.com"
                            class="ml-1 text-gray-900 underline hover:underline sm:ml-2 dark:text-white"
                        >
                            support@vanguardbackup.com
                        </a>
                        <span
                            class="mt-1 w-full text-xs italic text-gray-500 sm:ml-2 sm:mt-0 sm:w-auto dark:text-gray-400"
                        >
                            ({{ __('for urgent issues') }})
                        </span>
                    </li>
                    <li class="flex items-center">
                        <x-hugeicons-mailbox-01 class="mr-2 h-5 w-5 text-gray-600 sm:mr-3 dark:text-gray-400" />
                        <span class="font-medium">{{ __('Feedback:') }}</span>
                        <a
                            href="mailto:hello@vanguardbackup.com"
                            class="ml-1 text-gray-900 underline hover:underline sm:ml-2 dark:text-white"
                        >
                            hello@vanguardbackup.com
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </x-form-wrapper>
</div>
