<div>
    @section('title', __('Help Centre'))

    <x-slot name="header">
        {{ __('Help Centre') }}
    </x-slot>

    <div class="mx-auto max-w-5xl pb-6">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white">
                {{ __('How can we help you?') }}
            </h1>
            <p class="mt-3 text-gray-600 dark:text-gray-400">
                {{ __('Find the resources and documentation you need to get the most out of Vanguard') }}
            </p>
        </div>

        <!-- Documentation Cards - Emphasising self-support -->
        <div class="mb-12 grid grid-cols-1 gap-5 sm:grid-cols-3">
            @php
                $resources = [
                    [
                        'title' => 'Documentation',
                        'icon' => 'hugeicons-book-open-01',
                        'url' => 'https://docs.vanguardbackup.com',
                        'description' => 'Browse our comprehensive guides and API references',
                        'color' => 'blue',
                        'priority' => true,
                    ],
                    [
                        'title' => 'Community Discussions',
                        'icon' => 'hugeicons-chatting-01',
                        'url' => 'https://github.com/vanguardbackup/vanguard/discussions',
                        'description' => 'Get help from fellow Vanguard users in our community',
                        'color' => 'purple',
                        'priority' => false,
                    ],
                    [
                        'title' => 'Report a Bug',
                        'icon' => 'hugeicons-bug-02',
                        'url' => 'https://github.com/vanguardbackup/vanguard/issues',
                        'description' => 'Found a bug? Create an issue on our GitHub repository',
                        'color' => 'red',
                        'priority' => false,
                    ],
                ];
            @endphp

            @foreach ($resources as $resource)
                <a href="{{ $resource['url'] }}" class="group" target="_blank" rel="noopener noreferrer">
                    <div
                        class="{{ $resource['priority'] ? 'border-primary-200 dark:border-primary-800' : 'border-gray-200 dark:border-gray-700' }} flex h-full flex-col overflow-hidden rounded-xl border bg-white transition-all duration-200 hover:shadow-lg dark:bg-gray-800"
                    >
                        <div class="p-5">
                            <div class="flex items-center">
                                <div
                                    class="{{
                                        $resource['color'] === 'blue'
                                            ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                                            : ($resource['color'] === 'purple'
                                                ? 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400'
                                                : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400')
                                    }} flex h-10 w-10 items-center justify-center rounded-full"
                                >
                                    <x-dynamic-component :component="$resource['icon']" class="h-5 w-5" />
                                </div>
                                <h3 class="ml-3 text-lg font-medium text-gray-900 dark:text-white">
                                    {{ __($resource['title']) }}
                                </h3>
                            </div>
                            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ __($resource['description']) }}
                            </p>
                        </div>
                        <div
                            class="mt-auto border-t border-gray-100 bg-gray-50 p-4 text-sm font-medium text-gray-900 transition-colors duration-200 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white"
                        >
                            <div class="flex items-center">
                                <span>{{ __('View Resource') }}</span>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="ml-1.5 h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Feedback and Support Notice -->
        <div class="mb-10 rounded-xl border-none bg-white p-6 dark:bg-gray-800">
            <div class="flex flex-col space-y-6 sm:flex-row sm:space-x-6 sm:space-y-0">
                <!-- Feedback Email -->
                <div class="flex-1">
                    <div class="flex items-start">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400"
                        >
                            <x-hugeicons-mailbox-01 class="h-5 w-5" />
                        </div>
                        <div class="ml-3">
                            <h4 class="font-medium text-gray-900 dark:text-white">{{ __('Feedback') }}</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Have suggestions or comments about Vanguard? We\'d love to hear from you.') }}
                            </p>
                            <a
                                href="mailto:hello@vanguardbackup.com"
                                class="mt-2 inline-flex items-center text-sm text-primary-600 hover:underline dark:text-primary-400"
                            >
                                hello@vanguardbackup.com
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Self-hosted Notice -->
                <div class="flex-1">
                    <div class="flex items-start">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400"
                        >
                            <x-hugeicons-information-circle class="h-5 w-5" />
                        </div>
                        <div class="ml-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-medium text-gray-900 dark:text-white">
                                    {{ __('Self-hosted Installations') }}
                                </h4>
                                @php
                                    $isOfficialInstance = config('app.url') === 'https://app.vanguardbackup.com';
                                @endphp

                                <span
                                    class="{{ $isOfficialInstance ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                >
                                    @if ($isOfficialInstance)
                                        <span class="mr-1.5 flex h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                        {{ __('Official Instance') }}
                                    @else
                                        <span class="mr-1.5 flex h-1.5 w-1.5 rounded-full bg-gray-500"></span>
                                        {{ __('Self-hosted') }}
                                    @endif
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Please note that we can only provide limited assistance for self-hosted instances. If you\'re not using the official Vanguard instance (app.vanguardbackup.com), we cannot troubleshoot server or infrastructure-specific issues.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Premium Support -->
        <div
            class="rounded-xl bg-gradient-to-br from-gray-900 to-gray-800 p-6 text-white shadow-lg dark:from-gray-800 dark:to-gray-900"
        >
            <div class="flex flex-col sm:flex-row sm:items-center">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-white/20 sm:mb-0">
                    <x-hugeicons-customer-support class="h-6 w-6 text-white" />
                </div>
                <div class="sm:ml-5 sm:flex-1">
                    <h3 class="text-xl font-bold">{{ __('Premium Support') }}</h3>
                    <p class="mt-1 text-gray-300">
                        {{ __('Need priority assistance? We are available for dedicated help.') }}
                    </p>
                </div>
                <div class="mt-4 sm:ml-5 sm:mt-0">
                    <a
                        href="https://psp.vanguardbackup.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center rounded-lg bg-white px-5 py-2.5 text-sm font-medium text-gray-900 transition-colors duration-200 hover:bg-gray-100"
                    >
                        {{ __('Get Premium Support') }}
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="ml-2 h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                            />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
