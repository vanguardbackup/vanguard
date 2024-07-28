<script>
    function setupThemeSwitcher() {
        return {
            theme: localStorage.theme || 'system',
            setTheme(newTheme) {
                this.theme = newTheme;
                localStorage.theme = newTheme === 'system' ? '' : newTheme;
                this.updateTheme();
                this.$dispatch('close');
            },
            updateTheme() {
                if (this.theme === 'dark' || (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                window.dispatchEvent(new CustomEvent('themeChanged', { detail: this.theme }));
            },
            init() {
                this.updateTheme();
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => this.updateTheme());
            }
        }
    }
</script>

<div x-data="setupThemeSwitcher()" x-init="init()">
    <button
        @click="$dispatch('open-modal', 'theme-switcher')"
        class="mr-2.5 flex items-center text-sm font-medium text-gray-50 hover:text-gray-100 focus:outline-none transition duration-150 ease-in-out"
    >
        <span class="sr-only">{{ __('Toggle theme') }}</span>
        <svg x-show="theme === 'light'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <svg x-show="theme === 'dark'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
        <svg x-show="theme === 'system'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    </button>

    <x-modal name="theme-switcher" :focusable="true" maxWidth="md">
        <x-slot name="title">
            {{ __('Choose Your Theme') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Pick your colour scheme.') }}
        </x-slot>
        <x-slot name="icon">
            heroicon-o-swatch
        </x-slot>
        <div class="p-6 transform transition-all sm:max-w-sm sm:w-full">
            <div class="space-y-4">
                <button
                    @click="setTheme('light')"
                    class="flex items-center justify-between w-full px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-950 dark:focus:ring-white"
                    :class="{ 'ring-2 ring-gray-950 dark:ring-white': theme === 'light' }"
                >
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        {{ __('Light') }}
                    </div>
                    <svg x-show="theme === 'light'" class="w-5 h-5 text-gray-950 dark:text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
                <button
                    @click="setTheme('dark')"
                    class="flex items-center justify-between w-full px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-950 dark:focus:ring-white"
                    :class="{ 'ring-2 ring-gray-950 dark:ring-white': theme === 'dark' }"
                >
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        {{ __('Dark') }}
                    </div>
                    <svg x-show="theme === 'dark'" class="w-5 h-5 text-gray-950 dark:text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
                <button
                    @click="setTheme('system')"
                    class="flex items-center justify-between w-full px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-950 dark:focus:ring-white"
                    :class="{ 'ring-2 ring-gray-950 dark:ring-white': theme === 'system' }"
                >
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        {{ __('System') }}
                    </div>
                    <svg x-show="theme === 'system'" class="w-5 h-5 text-gray-950 dark:text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div class="mt-6">
                <x-secondary-button centered @click="$dispatch('close')">
                    {{ __('Close') }}
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
</div>
