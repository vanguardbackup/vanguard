<nav class="bg-white dark:bg-gray-800/50 rounded-[0.70rem] shadow-none border border-gray-200 dark:border-gray-800/30 p-2 w-full">
    <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-1 gap-1">
        <!-- Account Management -->
        <li class="col-span-2 sm:col-span-3 lg:col-span-1 pt-2 pb-1">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-2">Account</h3>
        </li>
        <li>
            <x-sidebar-nav-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>
                <span class="flex items-center">
                    @svg('hugeicons-user', 'h-5 w-5 mr-2')
                    <span>{{ __('My Profile') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li>
            <x-sidebar-nav-link :href="route('profile.api')" :active="request()->routeIs('profile.api*')" wire:navigate>
                <span class="flex items-center">
                    @svg('hugeicons-ticket-02', 'h-5 w-5 mr-2')
                    <span>{{ __('API Tokens') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li>
            <x-sidebar-nav-link :href="route('profile.connections')" :active="request()->routeIs('profile.connections*')" wire:navigate>
                <span class="flex items-center">
                    @svg('hugeicons-puzzle', 'h-5 w-5 mr-2')
                    <span>{{ __('Connections') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>

        <!-- Security -->
        <li class="col-span-2 sm:col-span-3 lg:col-span-1 pt-4 pb-1">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-2">Security</h3>
        </li>
        <li>
            <x-sidebar-nav-link :href="route('profile.mfa')" :active="request()->routeIs('profile.mfa*')" wire:navigate>
                <span class="flex items-center">
                    @svg('hugeicons-square-lock-02', 'h-5 w-5 mr-2')
                    <span>{{ __('Two-Factor Auth') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        @if (Config::get('session.driver') === 'database')
            <li>
                <x-sidebar-nav-link :href="route('profile.sessions')" :active="request()->routeIs('profile.sessions*')" wire:navigate>
                    <span class="flex items-center">
                        @svg('hugeicons-gps-signal-01', 'h-5 w-5 mr-2')
                        <span>{{ __('Active Sessions') }}</span>
                    </span>
                </x-sidebar-nav-link>
            </li>
        @endif
        <li>
            <x-sidebar-nav-link :href="route('profile.audit-logs')" :active="request()->routeIs('profile.audit-logs*')" wire:navigate>
                <span class="flex items-center">
                    @svg('hugeicons-license', 'h-5 w-5 mr-2')
                    <span>{{ __('Audit Logs') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>

        <!-- Preferences -->
        <li class="col-span-2 sm:col-span-3 lg:col-span-1 pt-4 pb-1">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-2">Preferences</h3>
        </li>
        <li>
            <x-sidebar-nav-link :href="route('tags.index')" :active="request()->routeIs('tags.*')" wire:navigate>
                <span class="flex items-center">
                    @svg('hugeicons-tags', 'h-5 w-5 mr-2')
                    <span>{{ __('Manage Tags') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li>
            <x-sidebar-nav-link :href="route('notification-streams.index')" :active="request()->routeIs('notification-streams.*')" wire:navigate>
                <span class="flex items-center">
                    @svg('hugeicons-notification-02', 'h-5 w-5 mr-2')
                    <span>{{ __('Notifications') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li>
            <x-sidebar-nav-link :href="route('profile.quiet-mode')" :active="request()->routeIs('profile.quiet-mode*')" wire:navigate>
                <span class="flex items-center">
                    @svg('hugeicons-notification-snooze-02', 'h-5 w-5 mr-2')
                    <span>{{ __('Quiet Mode') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li>
            <x-sidebar-nav-link :href="route('profile.experiments')" :active="request()->routeIs('profile.experiments*')" wire:navigate>
                <span class="flex items-center">
                    @svg('hugeicons-test-tube', 'h-5 w-5 mr-2')
                    <span>{{ __('Experiments') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>

        <!-- Support -->
        <li class="col-span-2 sm:col-span-3 lg:col-span-1 pt-4 pb-1">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-2">Support</h3>
        </li>
        <li>
            <x-sidebar-nav-link :href="route('profile.help')" :active="request()->routeIs('profile.help*')" wire:navigate>
                <span class="flex items-center">
                    @svg('hugeicons-mentoring', 'h-5 w-5 mr-2')
                    <span>{{ __('Get Help') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li>
            <x-sidebar-nav-link :href="route('account.remove-account')" :active="request()->routeIs('account.remove-account')" wire:navigate>
                <span class="flex items-center">
                    @svg('hugeicons-user-remove-01', 'h-5 w-5 mr-2')
                    <span>{{ __('Delete Account') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
    </ul>
</nav>
