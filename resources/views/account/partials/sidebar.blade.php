<nav class="bg-white dark:bg-gray-800/50 rounded-[0.70rem] shadow-none border border-gray-200 dark:border-gray-800/30 p-2 w-full">
    <ul class="flex lg:flex-col space-x-1 lg:space-x-0 lg:space-y-1 justify-around lg:justify-start">
        <li class="flex-1 lg:flex-initial">
            <x-sidebar-nav-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>
                <span class="flex flex-col lg:flex-row items-center justify-center lg:justify-start py-2 lg:py-1.5">
                    @svg('heroicon-o-user', 'h-6 w-6 lg:h-5 lg:w-5 lg:mr-2')
                    <span class="text-xs mt-1 lg:mt-0 lg:text-sm">{{ __('Profile') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li class="flex-1 lg:flex-initial">
            <x-sidebar-nav-link :href="route('tags.index')" :active="request()->routeIs('tags.*')" wire:navigate>
                <span class="flex flex-col lg:flex-row items-center justify-center lg:justify-start py-2 lg:py-1.5">
                    @svg('heroicon-o-tag', 'h-6 w-6 lg:h-5 lg:w-5 lg:mr-2')
                    <span class="text-xs mt-1 lg:mt-0 lg:text-sm">{{ __('Tags') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li class="flex-1 lg:flex-initial">
            <x-sidebar-nav-link :href="route('notification-streams.index')" :active="request()->routeIs('notification-streams.*')" wire:navigate>
                <span class="flex flex-col lg:flex-row items-center justify-center lg:justify-start py-2 lg:py-1.5">
                    @svg('heroicon-o-bell', 'h-6 w-6 lg:h-5 lg:w-5 lg:mr-2')
                    <span class="text-xs mt-1 lg:mt-0 lg:text-sm">{{ __('Notifications') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li class="flex-1 lg:flex-initial">
            <x-sidebar-nav-link :href="route('profile.api')" :active="request()->routeIs('profile.api*')" wire:navigate>
                <span class="flex flex-col lg:flex-row items-center justify-center lg:justify-start py-2 lg:py-1.5">
                    @svg('heroicon-o-code-bracket', 'h-6 w-6 lg:h-5 lg:w-5 lg:mr-2')
                    <span class="text-xs mt-1 lg:mt-0 lg:text-sm">{{ __('Manage API Tokens') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li class="flex-1 lg:flex-initial">
            <x-sidebar-nav-link :href="route('profile.mfa')" :active="request()->routeIs('profile.mfa*')" wire:navigate>
        <span class="flex flex-col lg:flex-row items-center justify-center lg:justify-start py-2 lg:py-1.5">
            @svg('heroicon-o-lock-closed', 'h-6 w-6 lg:h-5 lg:w-5 lg:mr-2')
            <span class="text-xs mt-1 lg:mt-0 lg:text-sm">{{ __('Manage 2FA') }}</span>
        </span>
            </x-sidebar-nav-link>
        </li>
        @if (Config::get('session.driver') === 'database')
            <li class="flex-1 lg:flex-initial">
                <x-sidebar-nav-link :href="route('profile.sessions')" :active="request()->routeIs('profile.sessions*')" wire:navigate>
            <span class="flex flex-col lg:flex-row items-center justify-center lg:justify-start py-2 lg:py-1.5">
                @svg('heroicon-o-globe-alt', 'h-6 w-6 lg:h-5 lg:w-5 lg:mr-2')
                <span class="text-xs mt-1 lg:mt-0 lg:text-sm">{{ __('Manage Sessions') }}</span>
            </span>
                </x-sidebar-nav-link>
            </li>
        @endif
        <li class="flex-1 lg:flex-initial">
            <x-sidebar-nav-link :href="route('profile.experiments')" :active="request()->routeIs('profile.experiments*')" wire:navigate>
            <span class="flex flex-col lg:flex-row items-center justify-center lg:justify-start py-2 lg:py-1.5">
                @svg('heroicon-o-beaker', 'h-6 w-6 lg:h-5 lg:w-5 lg:mr-2')
                <span class="text-xs mt-1 lg:mt-0 lg:text-sm">{{ __('Manage Experiments') }}</span>
            </span>
            </x-sidebar-nav-link>
        </li>
        <li class="flex-1 lg:flex-initial">
            <x-sidebar-nav-link :href="route('account.remove-account')" :active="request()->routeIs('account.remove-account')" wire:navigate>
                <span class="flex flex-col lg:flex-row items-center justify-center lg:justify-start py-2 lg:py-1.5">
                    @svg('heroicon-o-trash', 'h-6 w-6 lg:h-5 lg:w-5 lg:mr-2')
                    <span class="text-xs mt-1 lg:mt-0 lg:text-sm">{{ __('Remove Account') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
    </ul>
</nav>
