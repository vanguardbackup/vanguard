<div>
    @section('title', __('Connections'))
    <x-slot name="header">
        {{ __('Connections') }}
    </x-slot>

    <x-form-wrapper>
        <x-slot name="title">{{ __('External Service Connections') }}</x-slot>
        <x-slot name="description">
            {{ __('Link accounts for expanded features.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-puzzle</x-slot>

        <div class="space-y-6">
            @if (config('services.github.client_id') && config('services.github.client_secret'))
                <!-- GitHub Connection -->
                <div class="border border-gray-200 dark:border-gray-600 rounded-lg transition-all duration-200 overflow-hidden">
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center mb-4 sm:mb-0">
                                <div class="flex-shrink-0 mr-4">
                                    <svg class="h-10 w-10 text-gray-500 dark:text-gray-400" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>GitHub</title><path fill="currentColor" d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('GitHub') }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('Connect your GitHub account for seamless integration.') }}</p>
                                </div>
                            </div>
                            <div class="flex justify-end sm:ml-4 sm:flex-shrink-0 space-x-2">
                                @if ($this->isConnected('github'))
                                    @if ($this->hasRefreshToken('github'))
                                        <x-secondary-button
                                            wire:click="refresh('github')"
                                            wire:loading.attr="disabled"
                                            class="w-full sm:w-auto justify-center"
                                        >
                                            {{ __('Refresh Token') }}
                                        </x-secondary-button>
                                    @endif
                                    <x-danger-button
                                        wire:click="disconnect('github')"
                                        wire:loading.attr="disabled"
                                        class="w-full sm:w-auto justify-center"
                                    >
                                        {{ __('Disconnect') }}
                                    </x-danger-button>
                                @else
                                    <x-secondary-button
                                        wire:click="connect('github')"
                                        wire:loading.attr="disabled"
                                        class="w-full sm:w-auto justify-center"
                                    >
                                        {{ __('Connect') }}
                                    </x-secondary-button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if (config('services.gitlab.client_id') && config('services.gitlab.client_secret'))
                <!-- GitLab Connection -->
                <div class="border border-gray-200 dark:border-gray-600 rounded-lg transition-all duration-200 overflow-hidden">
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center mb-4 sm:mb-0">
                                <div class="flex-shrink-0 mr-4">
                                    <svg class="h-10 w-10 text-gray-500 dark:text-gray-400" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>GitLab</title><path fill="currentColor" d="m23.6004 9.5927-.0337-.0862L20.3.9814a.851.851 0 0 0-.3362-.405.8748.8748 0 0 0-.9997.0539.8748.8748 0 0 0-.29.4399l-2.2055 6.748H7.5375l-2.2057-6.748a.8573.8573 0 0 0-.29-.4412.8748.8748 0 0 0-.9997-.0537.8585.8585 0 0 0-.3362.4049L.4332 9.5015l-.0325.0862a6.0657 6.0657 0 0 0 2.0119 7.0105l.0113.0087.03.0213 4.976 3.7264 2.462 1.8633 1.4995 1.1321a1.0085 1.0085 0 0 0 1.2197 0l1.4995-1.1321 2.4619-1.8633 5.006-3.7489.0125-.01a6.0682 6.0682 0 0 0 2.0094-7.003z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('GitLab') }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('Link your GitLab account for extended functionality.') }}</p>
                                </div>
                            </div>
                            <div class="flex justify-end sm:ml-4 sm:flex-shrink-0 space-x-2">
                                @if ($this->isConnected('gitlab'))
                                    @if ($this->hasRefreshToken('gitlab'))
                                        <x-secondary-button
                                            wire:click="refresh('gitlab')"
                                            wire:loading.attr="disabled"
                                            class="w-full sm:w-auto justify-center"
                                        >
                                            {{ __('Refresh Token') }}
                                        </x-secondary-button>
                                    @endif
                                    <x-danger-button
                                        wire:click="disconnect('gitlab')"
                                        wire:loading.attr="disabled"
                                        class="w-full sm:w-auto justify-center"
                                    >
                                        {{ __('Disconnect') }}
                                    </x-danger-button>
                                @else
                                    <x-secondary-button
                                        wire:click="connect('gitlab')"
                                        wire:loading.attr="disabled"
                                        class="w-full sm:w-auto justify-center"
                                    >
                                        {{ __('Connect') }}
                                    </x-secondary-button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

                @if (config('services.bitbucket.client_id') && config('services.bitbucket.client_secret'))
                    <!-- Bitbucket Connection -->
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg transition-all duration-200 overflow-hidden">
                        <div class="p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center mb-4 sm:mb-0">
                                    <div class="flex-shrink-0 mr-4">
                                        <svg class="h-10 w-10 text-gray-500 dark:text-gray-400" fill="currentColor" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>Bitbucket</title><path d="M.778 1.213a.768.768 0 00-.768.892l3.263 19.81c.084.5.515.868 1.022.873H19.95a.772.772 0 00.77-.646l3.27-20.03a.768.768 0 00-.768-.891zM14.52 15.53H9.522L8.17 8.466h7.561z"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Bitbucket') }}</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('Attach your Bitbucket account to unlock additional features.') }}</p>
                                    </div>
                                </div>
                                <div class="flex justify-end sm:ml-4 sm:flex-shrink-0 space-x-2">
                                    @if ($this->isConnected('bitbucket'))
                                        @if ($this->hasRefreshToken('bitbucket'))
                                            <x-secondary-button
                                                wire:click="refresh('bitbucket')"
                                                wire:loading.attr="disabled"
                                                class="w-full sm:w-auto justify-center"
                                            >
                                                {{ __('Refresh Token') }}
                                            </x-secondary-button>
                                        @endif
                                        <x-danger-button
                                            wire:click="disconnect('bitbucket')"
                                            wire:loading.attr="disabled"
                                            class="w-full sm:w-auto justify-center"
                                        >
                                            {{ __('Disconnect') }}
                                        </x-danger-button>
                                    @else
                                        <x-secondary-button
                                            wire:click="connect('bitbucket')"
                                            wire:loading.attr="disabled"
                                            class="w-full sm:w-auto justify-center"
                                        >
                                            {{ __('Connect') }}
                                        </x-secondary-button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            <!-- You can add more connections here by duplicating the above structure -->

        </div>
    </x-form-wrapper>
</div>
