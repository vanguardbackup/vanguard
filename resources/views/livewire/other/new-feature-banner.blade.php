<div>
    @php
        $currentVersion = obtain_vanguard_version();
        $featureVersion = $latestFeature['version'] ?? '0.0.0';
        $showBanner = version_compare($currentVersion, $featureVersion, '>=');
    @endphp

    @if ($latestFeature && $showBanner)
        <div
            x-data="{
                show: true,
                copied: false,
                copyFeatureDetails() {
                    const details = `${this.$refs.title.textContent}\n${this.$refs.description.textContent}`
                    navigator.clipboard.writeText(details)
                    this.copied = true
                    setTimeout(() => (this.copied = false), 2000)
                },
            }"
            x-show="show"
            x-transition:enter="transition duration-300 ease-out"
            x-transition:enter-start="-translate-y-2 transform opacity-0"
            x-transition:enter-end="translate-y-0 transform opacity-100"
            x-transition:leave="transition duration-300 ease-in"
            x-transition:leave-start="translate-y-0 transform opacity-100"
            x-transition:leave-end="-translate-y-2 transform opacity-0"
            class="relative bg-gradient-to-r from-blue-500/90 to-blue-600/90 text-white shadow-lg"
            role="alert"
            aria-live="assertive"
        >
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between sm:flex-row">
                    <div class="mb-4 flex w-full items-center sm:mb-0 sm:w-auto">
                        <span class="flex rounded-full bg-blue-700/50 p-2 backdrop-blur-sm">
                            @svg('hugeicons-sparkles', 'h-6 w-6 text-white')
                        </span>
                        <p class="ml-3 text-base font-medium sm:text-lg">
                            {{ __('New Feature!') }}
                        </p>
                    </div>
                    <div
                        class="flex w-full flex-col items-stretch space-y-2 sm:w-auto sm:flex-row sm:items-center sm:space-x-3 sm:space-y-0"
                    >
                        <div class="flex-grow sm:flex-grow-0">
                            <p x-ref="title" class="text-sm font-bold sm:text-base">
                                {{ $latestFeature['title'] }}
                            </p>
                            <p x-ref="description" class="text-sm text-white/80">
                                {{ $latestFeature['description'] }}
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <a
                                href="{{ $latestFeature['github_url'] ?? 'https://github.com/vanguardbackup/vanguard' }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-full border border-white/25 bg-white/10 px-3 py-1 text-sm font-medium text-white backdrop-blur-sm transition-all duration-150 ease-out hover:-translate-y-0.5 hover:bg-white/20 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-blue-600"
                            >
                                @svg('hugeicons-github', 'mr-1 inline h-4 w-4')
                                {{ __('View on GitHub') }}
                            </a>
                            <button
                                wire:click="dismiss"
                                @click="show = false"
                                class="rounded-full border border-white/25 bg-white/10 px-3 py-1 text-sm font-medium text-white backdrop-blur-sm transition-all duration-150 ease-out hover:-translate-y-0.5 hover:bg-white/20 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-blue-600"
                            >
                                {{ __('Dismiss') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
