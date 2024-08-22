<div class="bg-white dark:bg-gray-900">
    @section('title', __('Need Help?'))

    <x-slot name="header">
        {{ __('Need Help?') }}
    </x-slot>

    <x-form-wrapper>
        <x-slot name="icon">
            heroicon-o-lifebuoy
        </x-slot>

        <x-slot name="title">
            {{ __('Need Help?') }}
        </x-slot>

        <x-slot name="description">
            {{ __('If you are a bit stuck and need some support, this should help you receive it!') }}
        </x-slot>

        <div class="space-y-6 sm:space-y-8">
            <p class="text-sm sm:text-base text-gray-700 dark:text-gray-300 leading-relaxed">
                {{ __('Vanguard offers various resources to help you get the most out of our backup solution. Here are some quick links to get you started:') }}
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                @php
                    $resources = [
                        ['title' => 'Documentation', 'icon' => 'heroicon-o-book-open', 'url' => 'https://docs.vanguardbackup.com', 'description' => 'Comprehensive guides and API references'],
                        ['title' => 'GitHub Discussions', 'icon' => 'heroicon-o-chat-bubble-left-right', 'url' => 'https://github.com/vanguardbackup/vanguard/discussions', 'description' => 'Community-driven Q&A and discussions'],
                        ['title' => 'Vanguard Website', 'icon' => 'heroicon-o-globe-alt', 'url' => 'https://vanguardbackup.com', 'description' => 'Product information and latest updates'],
                    ];
                @endphp

                @foreach ($resources as $resource)
                    <a href="{{ $resource['url'] }}" class="group block" target="_blank" rel="noopener noreferrer">
                        <div class="h-full flex flex-col p-4 sm:p-6 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 transition duration-300 ease-in-out hover:bg-gray-100 dark:hover:bg-gray-700 hover:shadow-md">
                            <div class="flex items-center mb-3 sm:mb-4">
                                <x-dynamic-component :component="$resource['icon']" class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 dark:text-blue-400 transition-transform duration-300 ease-in-out transform group-hover:scale-110"/>
                                <h3 class="ml-2 sm:ml-3 text-base sm:text-lg font-semibold text-gray-900 dark:text-white">{{ __($resource['title']) }}</h3>
                            </div>
                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 flex-grow">{{ __($resource['description']) }}</p>
                            <div class="mt-3 sm:mt-4 flex items-center text-blue-600 dark:text-blue-400 text-xs sm:text-sm font-medium">
                                {{ __('Learn more') }}
                                <x-heroicon-o-arrow-right class="ml-1 w-3 h-3 sm:w-4 sm:h-4 transition-transform duration-300 ease-in-out transform group-hover:translate-x-1" />
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-3 sm:mb-4">{{ __('Need Further Assistance?') }}</h3>
                <p class="text-sm sm:text-base text-gray-700 dark:text-gray-300 mb-4">
                    {{ __('If you couldn\'t find what you\'re looking for in the resources above, we can help:') }}
                </p>
                <ul class="space-y-3 text-sm sm:text-base text-gray-700 dark:text-gray-300">
                    <li class="flex flex-wrap items-center">
                        <x-heroicon-o-envelope class="w-5 h-5 mr-2 sm:mr-3 text-blue-600 dark:text-blue-400" />
                        <span class="font-medium">{{ __('Support:') }}</span>
                        <a href="mailto:support@vanguardbackup.com" class="ml-1 sm:ml-2 text-blue-600 dark:text-blue-400 hover:underline">support@vanguardbackup.com</a>
                        <span class="w-full sm:w-auto sm:ml-2 mt-1 sm:mt-0 text-xs italic text-gray-500 dark:text-gray-400">({{ __('for urgent issues') }})</span>
                    </li>
                    <li class="flex items-center">
                        <x-heroicon-o-chat-bubble-left-ellipsis class="w-5 h-5 mr-2 sm:mr-3 text-blue-600 dark:text-blue-400" />
                        <span class="font-medium">{{ __('Feedback:') }}</span>
                        <a href="mailto:hello@vanguardbackup.com" class="ml-1 sm:ml-2 text-blue-600 dark:text-blue-400 hover:underline">hello@vanguardbackup.com</a>
                    </li>
                </ul>
            </div>
        </div>
    </x-form-wrapper>
</div>