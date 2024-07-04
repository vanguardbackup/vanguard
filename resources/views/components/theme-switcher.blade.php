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
        theme: localStorage.theme,
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
            this.theme = undefined
            localStorage.removeItem('theme')
            setDarkClass()
        },
    }"
    @click.outside="menu = false"
>
    <button
        x-cloak
        class="block p-1 hover:text-gray-200 ease-in-out"
        :class="theme ? 'text-white dark:text-gray-300' : 'text-gray-400 dark:text-gray-600 hover:text-gray-500 focus:text-gray-500 dark:hover:text-gray-500 dark:focus:text-gray-500'"
        @click="menu = ! menu"
    >
        @svg('heroicon-o-sun', 'block dark:block w-5 h-5')
        @svg('heroicon-o-moon', 'hidden dark:hidden w-5 h-5')
    </button>

    <div x-show="menu" class="text-sm z-10 absolute origin-top-right right-0 bg-white dark:bg-gray-700 rounded-md ring-1 ring-gray-900/5 shadow-lg flex flex-col" style="display: none;" @click="menu = false">
        <button class="flex items-center px-6 py-4 gap-3 dark:hover:bg-gray-800" :class="theme === 'light' ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'" @click="lightMode()">
            @svg('heroicon-o-sun', 'w-5 h-5')
            {{ __('Light') }}
        </button>
        <button class="flex items-center px-6 py-4 gap-3 hover:bg-gray-100 dark:hover:bg-gray-800" :class="theme === 'dark' ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'" @click="darkMode()">
            @svg('heroicon-o-moon', 'w-5 h-5')
            {{ __('Dark') }}
        </button>
        <button class="flex items-center px-6 py-4 gap-3 hover:bg-gray-100 dark:hover:bg-gray-800" :class="theme === undefined ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'" @click="systemMode()">
            @svg('heroicon-o-computer-desktop', 'w-5 h-5')
            {{ __('System') }}
        </button>
    </div>
</div>
