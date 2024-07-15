<nav class="bg-gray-100 dark:bg-gray-800 rounded-lg p-1.5 lg:p-3 w-full">
    <ul class="flex lg:flex-col space-x-1 lg:space-x-0 lg:space-y-2 justify-center lg:justify-start">
        <li class="flex-1 lg:flex-none">
            <x-sidebar-nav-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>
                <span class="lg:hidden flex items-center justify-center h-10">
                    @svg('heroicon-o-user', 'h-5 w-5')
                </span>
                <span class="hidden lg:inline-flex items-center w-full">
                    @svg('heroicon-o-user', 'h-5 w-5 mr-3')
                    {{ __('Profile') }}
                </span>
            </x-sidebar-nav-link>
        </li>
        <li class="flex-1 lg:flex-none">
            <x-sidebar-nav-link :href="route('tags.index')" :active="request()->routeIs('tags.index')" wire:navigate>
                <span class="lg:hidden flex items-center justify-center h-10">
                    @svg('heroicon-o-tag', 'h-5 w-5')
                </span>
                <span class="hidden lg:inline-flex items-center w-full">
                    @svg('heroicon-o-tag', 'h-5 w-5 mr-3')
                    {{ __('Tags') }}
                </span>
            </x-sidebar-nav-link>
        </li>
        <li class="flex-1 lg:flex-none">
            <x-sidebar-nav-link :href="route('account.remove-account')" :active="request()->routeIs('account.remove-account')" wire:navigate>
                <span class="lg:hidden flex items-center justify-center h-10">
                    @svg('heroicon-o-trash', 'h-5 w-5')
                </span>
                <span class="hidden lg:inline-flex items-center w-full">
                    @svg('heroicon-o-trash', 'h-5 w-5 mr-3')
                    {{ __('Remove Account') }}
                </span>
            </x-sidebar-nav-link>
        </li>
    </ul>
</nav>
