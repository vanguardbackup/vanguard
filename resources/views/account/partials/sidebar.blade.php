<nav
    class="w-full rounded-[0.70rem] border border-gray-200 bg-white p-2 shadow-none dark:border-gray-800/30 dark:bg-gray-800/50"
>
    <ul class="grid grid-cols-2 gap-1 sm:grid-cols-3 lg:grid-cols-1">
        <!-- Account Management -->
        <li class="col-span-2 pb-1 pt-2 sm:col-span-3 lg:col-span-1">
            <h3 class="px-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Account
            </h3>
        </li>
        <li>
            <x-sidebar-nav-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>
                <span class="flex items-center">
                    @svg('hugeicons-user', 'mr-2 h-5 w-5')
                    <span>{{ __('My Profile') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        @if (year_in_review_active())
            <li>
                <x-sidebar-nav-link
                    :href="route('profile.year-in-review')"
                    :active="request()->routeIs('profile.year-in-review*')"
                    wire:navigate
                >
                <span class="flex items-center">
                    @svg('hugeicons-cheese-cake-01', 'mr-2 h-5 w-5')
                    <span>{{ __('Year in Review') }}</span>
                </span>
                </x-sidebar-nav-link>
            </li>
        @endif
        <li>
            <x-sidebar-nav-link
                :href="route('profile.api')"
                :active="request()->routeIs('profile.api*')"
                wire:navigate
            >
                <span class="flex items-center">
                    @svg('hugeicons-ticket-02', 'mr-2 h-5 w-5')
                    <span>{{ __('API Tokens') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li>
            <x-sidebar-nav-link
                :href="route('profile.connections')"
                :active="request()->routeIs('profile.connections*')"
                wire:navigate
            >
                <span class="flex items-center">
                    @svg('hugeicons-puzzle', 'mr-2 h-5 w-5')
                    <span>{{ __('Connections') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>

        <!-- Security -->
        <li class="col-span-2 pb-1 pt-4 sm:col-span-3 lg:col-span-1">
            <h3 class="px-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Security
            </h3>
        </li>
        <li>
            <x-sidebar-nav-link
                :href="route('profile.mfa')"
                :active="request()->routeIs('profile.mfa*')"
                wire:navigate
            >
                <span class="flex items-center">
                    @svg('hugeicons-square-lock-02', 'mr-2 h-5 w-5')
                    <span>{{ __('Two-Factor Auth') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        @if (Config::get('session.driver') === 'database')
            <li>
                <x-sidebar-nav-link
                    :href="route('profile.sessions')"
                    :active="request()->routeIs('profile.sessions*')"
                    wire:navigate
                >
                    <span class="flex items-center">
                        @svg('hugeicons-gps-signal-01', 'mr-2 h-5 w-5')
                        <span>{{ __('Active Sessions') }}</span>
                    </span>
                </x-sidebar-nav-link>
            </li>
        @endif

        <li>
            <x-sidebar-nav-link
                :href="route('profile.audit-logs')"
                :active="request()->routeIs('profile.audit-logs*')"
                wire:navigate
            >
                <span class="flex items-center">
                    @svg('hugeicons-license', 'mr-2 h-5 w-5')
                    <span>{{ __('Audit Logs') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>

        <!-- Preferences -->
        <li class="col-span-2 pb-1 pt-4 sm:col-span-3 lg:col-span-1">
            <h3 class="px-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Preferences
            </h3>
        </li>
        <li>
            <x-sidebar-nav-link :href="route('tags.index')" :active="request()->routeIs('tags.*')" wire:navigate>
                <span class="flex items-center">
                    @svg('hugeicons-tags', 'mr-2 h-5 w-5')
                    <span>{{ __('Manage Tags') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li>
            <x-sidebar-nav-link
                :href="route('notification-streams.index')"
                :active="request()->routeIs('notification-streams.*')"
                wire:navigate
            >
                <span class="flex items-center">
                    @svg('hugeicons-notification-02', 'mr-2 h-5 w-5')
                    <span>{{ __('Notifications') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li>
            <x-sidebar-nav-link
                :href="route('profile.quiet-mode')"
                :active="request()->routeIs('profile.quiet-mode*')"
                wire:navigate
            >
                <span class="flex items-center">
                    @svg('hugeicons-notification-snooze-02', 'mr-2 h-5 w-5')
                    <span>{{ __('Quiet Mode') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li>
            <x-sidebar-nav-link
                :href="route('profile.experiments')"
                :active="request()->routeIs('profile.experiments*')"
                wire:navigate
            >
                <span class="flex items-center">
                    @svg('hugeicons-test-tube', 'mr-2 h-5 w-5')
                    <span>{{ __('Experiments') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>

        <!-- Support -->
        <li class="col-span-2 pb-1 pt-4 sm:col-span-3 lg:col-span-1">
            <h3 class="px-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Support
            </h3>
        </li>
        <li>
            <x-sidebar-nav-link
                :href="route('profile.help')"
                :active="request()->routeIs('profile.help*')"
                wire:navigate
            >
                <span class="flex items-center">
                    @svg('hugeicons-mentoring', 'mr-2 h-5 w-5')
                    <span>{{ __('Get Help') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
        <li>
            <x-sidebar-nav-link
                :href="route('account.remove-account')"
                :active="request()->routeIs('account.remove-account')"
                wire:navigate
            >
                <span class="flex items-center">
                    @svg('hugeicons-user-remove-01', 'mr-2 h-5 w-5')
                    <span>{{ __('Delete Account') }}</span>
                </span>
            </x-sidebar-nav-link>
        </li>
    </ul>
</nav>
