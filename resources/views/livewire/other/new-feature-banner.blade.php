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
            const details = `${this.$refs.title.textContent}\n${this.$refs.description.textContent}`;
            navigator.clipboard.writeText(details);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        }
    }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="bg-gradient-to-r from-blue-500/90 to-blue-600/90 text-white relative shadow-lg"
            role="alert"
            aria-live="assertive"
        >
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row items-center justify-between">
                    <div class="flex items-center w-full sm:w-auto mb-4 sm:mb-0">
                <span class="flex p-2 rounded-full bg-blue-700/50 backdrop-blur-sm">
                    @svg('hugeicons-sparkles', 'h-6 w-6 text-white')
                </span>
                        <p class="ml-3 font-medium text-base sm:text-lg">
                            {{ __('New Feature!') }}
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-2 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
                        <div class="flex-grow sm:flex-grow-0">
                            <p x-ref="title" class="font-bold text-sm sm:text-base">{{ $latestFeature['title'] }}</p>
                            <p x-ref="description" class="text-sm text-white/80">{{ $latestFeature['description'] }}</p>
                        </div>
                        <div class="flex space-x-2">
                            <a
                                href="{{ $latestFeature['github_url'] ?? 'https://github.com/vanguardbackup/vanguard' }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="bg-white/10 hover:bg-white/20 backdrop-blur-sm px-3 py-1 text-sm font-medium text-white rounded-full border border-white/25 focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-blue-600 transition-all duration-150 ease-out hover:shadow-lg hover:-translate-y-0.5"
                            >
                                @svg('hugeicons-github', 'h-4 w-4 inline mr-1')
                                {{ __('View on GitHub') }}
                            </a>
                            <button
                                wire:click="dismiss"
                                @click="show = false"
                                class="bg-white/10 hover:bg-white/20 backdrop-blur-sm px-3 py-1 text-sm font-medium text-white rounded-full border border-white/25 focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-blue-600 transition-all duration-150 ease-out hover:shadow-lg hover:-translate-y-0.5"
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
