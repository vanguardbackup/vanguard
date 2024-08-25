<div x-data="setupThemeSwitcher()" x-init="init()">
    <button
        @click="$dispatch('open-modal', 'theme-switcher')"
        class="mr-2.5 flex items-center text-sm font-medium text-gray-50 hover:text-gray-100 focus:outline-none transition duration-150 ease-in-out"
    >
        <span class="sr-only">{{ __('Toggle theme') }}</span>
        <svg x-show="theme === 'light'" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">
            <path d="M17 12C17 14.7614 14.7614 17 12 17C9.23858 17 7 14.7614 7 12C7 9.23858 9.23858 7 12 7C14.7614 7 17 9.23858 17 12Z" stroke="currentColor" stroke-width="1.5" />
            <path d="M12 2C11.6227 2.33333 11.0945 3.2 12 4M12 20C12.3773 20.3333 12.9055 21.2 12 22M19.5 4.50271C18.9685 4.46982 17.9253 4.72293 18.0042 5.99847M5.49576 17.5C5.52865 18.0315 5.27555 19.0747 4 18.9958M5.00271 4.5C4.96979 5.03202 5.22315 6.0763 6.5 5.99729M18 17.5026C18.5315 17.4715 19.5747 17.7108 19.4958 18.9168M22 12C21.6667 11.6227 20.8 11.0945 20 12M4 11.5C3.66667 11.8773 2.8 12.4055 2 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </svg>

        <svg x-show="theme === 'dark'" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">
            <path d="M21.5 14.0784C20.3003 14.7189 18.9301 15.0821 17.4751 15.0821C12.7491 15.0821 8.91792 11.2509 8.91792 6.52485C8.91792 5.06986 9.28105 3.69968 9.92163 2.5C5.66765 3.49698 2.5 7.31513 2.5 11.8731C2.5 17.1899 6.8101 21.5 12.1269 21.5C16.6849 21.5 20.503 18.3324 21.5 14.0784Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>

        <svg x-show="theme === 'system'" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">
            <path d="M14 2H10C6.72077 2 5.08116 2 3.91891 2.81382C3.48891 3.1149 3.1149 3.48891 2.81382 3.91891C2 5.08116 2 6.72077 2 10C2 13.2792 2 14.9188 2.81382 16.0811C3.1149 16.5111 3.48891 16.8851 3.91891 17.1862C5.08116 18 6.72077 18 10 18H14C17.2792 18 18.9188 18 20.0811 17.1862C20.5111 16.8851 20.8851 16.5111 21.1862 16.0811C22 14.9188 22 13.2792 22 10C22 6.72077 22 5.08116 21.1862 3.91891C20.8851 3.48891 20.5111 3.1149 20.0811 2.81382C18.9188 2 17.2792 2 14 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            <path d="M11 15H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M14.5 22L14.1845 21.5811C13.4733 20.6369 13.2969 19.1944 13.7468 18M9.5 22L9.8155 21.5811C10.5267 20.6369 10.7031 19.1944 10.2532 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            <path d="M7 22H17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </svg>

    </button>

    <x-modal name="theme-switcher" :focusable="true" maxWidth="md">
        <x-slot name="title">
            {{ __('Theme Preferences') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Choose your visual style') }}
        </x-slot>
        <x-slot name="icon">
            hugeicons-swatch
        </x-slot>
        <div class="p-6 transform transition-all sm:max-w-sm sm:w-full">
            <div class="space-y-4">
                <template x-for="(option, index) in [
                    { value: 'light', label: 'Light', icon: 'sun' },
                    { value: 'dark', label: 'Dark', icon: 'moon' },
                    { value: 'system', label: 'System', icon: 'computer-desktop' }
                ]" :key="index">
                    <button
                        @click="setTheme(option.value)"
                        class="flex items-center justify-between w-full px-4 py-3 text-left text-sm font-medium rounded-lg transition-colors duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-primary-400"
                        :class="{
                            'bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-100': theme === option.value,
                            'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600': theme !== option.value
                        }"
                    >
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3" :class="{
                                'text-yellow-400': option.value === 'light',
                                'text-indigo-500': option.value === 'dark',
                                'text-green-500': option.value === 'system'
                            }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x-bind:d="getIconPath(option.icon)" />
                            </svg>
                            <span x-text="option.label"></span>
                        </div>
                        <svg x-show="theme === option.value" class="w-5 h-5 text-primary-500 dark:text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </template>
            </div>
            <div class="mt-6">
                <x-secondary-button centered @click="$dispatch('close')">
                    {{ __('Close') }}
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
</div>

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
            },
            getIconPath(icon) {
                const paths = {
                    sun: 'M17 12C17 14.7614 14.7614 17 12 17C9.23858 17 7 14.7614 7 12C7 9.23858 9.23858 7 12 7C14.7614 7 17 9.23858 17 12Z M12 2C11.6227 2.33333 11.0945 3.2 12 4M12 20C12.3773 20.3333 12.9055 21.2 12 22M19.5 4.50271C18.9685 4.46982 17.9253 4.72293 18.0042 5.99847M5.49576 17.5C5.52865 18.0315 5.27555 19.0747 4 18.9958M5.00271 4.5C4.96979 5.03202 5.22315 6.0763 6.5 5.99729M18 17.5026C18.5315 17.4715 19.5747 17.7108 19.4958 18.9168M22 12C21.6667 11.6227 20.8 11.0945 20 12M4 11.5C3.66667 11.8773 2.8 12.4055 2 11.5',
                    moon: 'M21.5 14.0784C20.3003 14.7189 18.9301 15.0821 17.4751 15.0821C12.7491 15.0821 8.91792 11.2509 8.91792 6.52485C8.91792 5.06986 9.28105 3.69968 9.92163 2.5C5.66765 3.49698 2.5 7.31513 2.5 11.8731C2.5 17.1899 6.8101 21.5 12.1269 21.5C16.6849 21.5 20.503 18.3324 21.5 14.0784Z',
                    'computer-desktop': 'M14 2H10C6.72077 2 5.08116 2 3.91891 2.81382C3.48891 3.1149 3.1149 3.48891 2.81382 3.91891C2 5.08116 2 6.72077 2 10C2 13.2792 2 14.9188 2.81382 16.0811C3.1149 16.5111 3.48891 16.8851 3.91891 17.1862C5.08116 18 6.72077 18 10 18H14C17.2792 18 18.9188 18 20.0811 17.1862C20.5111 16.8851 20.8851 16.5111 21.1862 16.0811C22 14.9188 22 13.2792 22 10C22 6.72077 22 5.08116 21.1862 3.91891C20.8851 3.48891 20.5111 3.1149 20.0811 2.81382C18.9188 2 17.2792 2 14 2Z M11 15H13 M14.5 22L14.1845 21.5811C13.4733 20.6369 13.2969 19.1944 13.7468 18M9.5 22L9.8155 21.5811C10.5267 20.6369 10.7031 19.1944 10.2532 18 M7 22H17'
                };
                return paths[icon] || '';
            }
        }
    }
</script>
