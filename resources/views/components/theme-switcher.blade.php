<script>
    setDarkClass = () => {
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: 'dark' }))
        } else {
            document.documentElement.classList.remove('dark')
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: 'light' }))
        }
    }

    setDarkClass()

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', setDarkClass)
</script>

<div
    class="relative"
    x-data="{
        menu: false,
        theme: localStorage.theme || 'system',
        darkMode() {
            this.theme = 'dark'
            localStorage.theme = 'dark'
            setDarkClass()
        },
        lightMode() {
            this.theme = 'light'
            localStorage.theme = 'light'
            setDarkClass()
        },
        systemMode() {
            this.theme = 'system'
            localStorage.removeItem('theme')
            setDarkClass()
        },
    }"
    @click.outside="menu = false"
>
    <button
        x-cloak
        class="block p-1 text-gray-400 hover:text-gray-200 ease-in-out"
        :class="{'text-white dark:text-gray-300': theme !== 'system'}"
        @click="menu = !menu"
    >
        <template x-if="theme === 'light'">
            @svg('heroicon-o-sun', 'w-5 h-5')
        </template>
        <template x-if="theme === 'dark'">
            @svg('heroicon-o-moon', 'w-5 h-5')
        </template>
        <template x-if="theme === 'system'">
            @svg('heroicon-o-computer-desktop', 'w-5 h-5')
        </template>
    </button>

    <div x-show="menu" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-50" style="display: none;">
        <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
            <button class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600" role="menuitem" @click="lightMode(); menu = false">
                @svg('heroicon-o-sun', 'w-5 h-5 mr-3')
                {{ __('Light') }}
            </button>
            <button class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600" role="menuitem" @click="darkMode(); menu = false">
                @svg('heroicon-o-moon', 'w-5 h-5 mr-3')
                {{ __('Dark') }}
            </button>
            <button class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600" role="menuitem" @click="systemMode(); menu = false">
                @svg('heroicon-o-computer-desktop', 'w-5 h-5 mr-3')
                {{ __('System') }}
            </button>
        </div>
    </div>
</div>
