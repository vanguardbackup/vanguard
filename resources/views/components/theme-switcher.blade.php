<div x-data="setupThemeSwitcher()" x-init="init()">
    <x-modal name="theme-switcher" :focusable="true" maxWidth="2xl">
        <x-slot name="title">
            {{ __('Appearance Settings') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Customize the interface to suit your preferences.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-paint-board</x-slot>
        <div class="transform transition-all sm:w-full sm:max-w-2xl">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <template
                    x-for="
                        (option, index) in
                            [
                                {
                                    value: 'light',
                                    label: 'Light',
                                    icon: 'sun',
                                    description: 'Bright and clear for daytime use.',
                                    color: 'yellow',
                                },
                                {
                                    value: 'dark',
                                    label: 'Dark',
                                    icon: 'moon',
                                    description: 'Easy on the eyes in low-light environments.',
                                    color: 'indigo',
                                },
                                {
                                    value: 'system',
                                    label: 'System',
                                    icon: 'computer-desktop',
                                    description: 'Automatically matches your device settings.',
                                    color: 'green',
                                },
                            ]
                    "
                    :key="index"
                >
                    <button
                        @click="setTheme(option.value)"
                        class="group relative flex w-full flex-col items-center overflow-hidden rounded-lg border px-4 py-5 text-center transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-primary-400"
                        :class="{
                            'bg-primary-50 dark:bg-primary-900 text-primary-700 dark:text-primary-100 border-primary-200 dark:border-primary-700 shadow-md': theme === option.value,
                            'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 border-gray-200 dark:border-gray-600 hover:shadow-md': theme !== option.value
                        }"
                    >
                        <div
                            class="absolute inset-0 opacity-0 group-hover:opacity-10"
                            :class="`bg-${option.color}-500`"
                        ></div>
                        <svg
                            class="mb-4 h-12 w-12 transition-transform duration-300 ease-in-out group-hover:scale-110"
                            :class="{
                        'text-amber-500 dark:text-amber-400': option.value === 'light',
                        'text-blue-600 dark:text-blue-400': option.value === 'dark',
                        'text-emerald-600 dark:text-emerald-400': option.value === 'system'}"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                x-bind:d="getIconPath(option.icon)"
                            />
                        </svg>
                        <span x-text="option.label" class="mb-2 text-lg font-semibold"></span>
                        <p class="text-sm" x-text="option.description"></p>
                        <div
                            x-show="theme === option.value"
                            class="absolute right-2 top-2 rounded-full p-1"
                            :class="`bg-${option.color}-100 dark:bg-${option.color}-900`"
                        >
                            <svg
                                class="h-5 w-5"
                                :class="`text-${option.color}-500 dark:text-${option.color}-400`"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                    </button>
                </template>
            </div>
            <div class="mt-8 text-center">
                <x-secondary-button
                    centered
                    @click="$dispatch('close')"
                    class="px-6 py-2 transition-all duration-300 ease-in-out hover:bg-gray-100 dark:hover:bg-gray-700"
                >
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
            },
            updateTheme() {
                if (
                    this.theme === 'dark' ||
                    (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)
                ) {
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
                    sun: 'M19.0398 10.3679C17.9251 9.7936 17.7602 9.33788 18.1319 8.17618C18.3274 7.56515 18.9174 6.39175 18.4745 5.76736C17.8935 4.94821 16.5388 5.63909 15.8237 5.86792C14.6294 6.25004 14.1906 6.04435 13.6319 4.96008C13.3117 4.33848 12.8801 3.00008 11.9998 3.00008C11.1194 3.00008 10.6878 4.33848 10.3676 4.96008C9.80895 6.04435 9.37015 6.25004 8.17585 5.86792C7.46067 5.63909 6.10601 4.94821 5.52499 5.76736C5.08211 6.39175 5.67208 7.56515 5.86759 8.17618C6.23928 9.33788 6.07445 9.7936 4.95975 10.3679C4.33819 10.6881 2.99986 11.1197 2.99976 12C2.99965 12.8804 4.33812 13.312 4.95975 13.6323C6.07445 14.2066 6.23928 14.6623 5.86759 15.824C5.65428 16.4906 5.0124 17.7434 5.63737 18.3656C6.26014 18.9857 7.51055 18.3451 8.17585 18.1322C9.37015 17.7501 9.80895 17.9558 10.3676 19.0401C10.6878 19.6617 11.1194 21.0001 11.9998 21.0001C12.8801 21.0001 13.3117 19.6617 13.6319 19.0401C14.1906 17.9558 14.6294 17.7501 15.8237 18.1322C16.489 18.3451 17.7394 18.9857 18.3621 18.3656C18.9871 17.7434 18.3452 16.4906 18.1319 15.824C17.7602 14.6623 17.9251 14.2066 19.0398 13.6323C19.6614 13.312 20.9999 12.8804 20.9998 12C20.9997 11.1197 19.6613 10.6881 19.0398 10.3679Z',
                    moon: 'M21.0985 7.84477C20.458 8.55417 19.5311 9 18.5 9C16.567 9 15 7.433 15 5.5C15 4.46895 15.4458 3.54203 16.1552 2.90149M21.0985 7.84477C21.6774 9.11025 22 10.5174 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C13.4826 2 14.8898 2.32262 16.1552 2.90149M21.0985 7.84477C20.0998 5.66155 18.3384 3.90018 16.1552 2.90149 M10 8H10.0064 M7 14H7.00635 M16 16C16 17.1046 15.1046 18 14 18C12.8954 18 12 17.1046 12 16C12 14.8954 12.8954 14 14 14C15.1046 14 16 14.8954 16 16Z',
                    'computer-desktop':
                        'M20 14.5V6.5C20 4.61438 20 3.67157 19.4142 3.08579C18.8284 2.5 17.8856 2.5 16 2.5H8C6.11438 2.5 5.17157 2.5 4.58579 3.08579C4 3.67157 4 4.61438 4 6.5V14.5 M12 5.5H12.009 M3.49762 15.5154L4.01953 14.5H19.9518L20.5023 15.5154C21.9452 18.177 22.3046 19.5077 21.7561 20.5039C21.2077 21.5 19.7536 21.5 16.8454 21.5L7.15462 21.5C4.24642 21.5 2.79231 21.5 2.24387 20.5039C1.69543 19.5077 2.05474 18.177 3.49762 15.5154Z',
                };
                return paths[icon] || '';
            },
        };
    }
</script>
