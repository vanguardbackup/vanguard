<div>
    <x-table.table-row>
        <div class="col-span-3">
            <div class="flex items-center text-sm font-medium">
                <div class="relative mr-3">
                    <img
                        class="h-8 w-8 rounded-full object-cover"
                        src="{{ $user->gravatar() }}"
                        alt="{{ $user->name }}"
                    />
                </div>
                <div>
                    <span class="font-medium text-gray-900 dark:text-gray-100">
                        {{ $user->name }}
                    </span>
                    <p
                        class="cursor-pointer truncate text-xs text-gray-700 dark:text-gray-400"
                        x-data="{ revealed: false }"
                        x-on:click="revealed = !revealed"
                    >
                        <span x-show="!revealed" class="blur-sm">••••••@••••••</span>
                        <span x-show="revealed">{{ $user->email }}</span>
                        <span
                            class="ml-1 text-blue-500"
                            x-text="revealed ? '(Click to hide)' : '(Click to view)'"
                        ></span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-span-3">
            <p
                class="{{ $user->hasSuspendedAccount() ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}"
            >
                {{ $user->hasSuspendedAccount() ? __('Suspended') : __('No Suspension') }}
            </p>
        </div>
        <div class="text-gray-900 dark:text-gray-100">
            {{ $user->isAdmin() ? __('Admin') : __('Standard') }}
        </div>
        <div class="col-span-3 flex justify-center space-x-2">
            @if (! $user->isAdmin())
                @if ($user->hasSuspendedAccount())
                    <x-secondary-button
                        iconOnly
                        x-on:click="$dispatch('open-modal', 'unsuspend-user-modal-{{ $user->id }}')"
                        title="{{ __('Manage Suspension') }}"
                    >
                        <span class="sr-only">
                            {{ __('Manage Suspension') }}
                        </span>
                        <x-hugeicons-user-check-01 class="h-4 w-4" />
                    </x-secondary-button>
                @else
                    <x-secondary-button
                        iconOnly
                        x-on:click="$dispatch('open-modal', 'suspend-user-modal-{{ $user->id }}')"
                        title="{{ __('Suspend User') }}"
                    >
                        <span class="sr-only">
                            {{ __('Suspend User') }}
                        </span>
                        <x-hugeicons-user-block-01 class="h-4 w-4" />
                    </x-secondary-button>
                @endif
            @endif

            <x-secondary-button
                iconOnly
                x-on:click="$dispatch('open-modal', 'suspension-history-modal-{{ $user->id }}')"
                title="{{ __('Suspension History') }}"
            >
                <span class="sr-only">
                    {{ __('Suspension History') }}
                </span>
                <x-hugeicons-work-history class="h-4 w-4" />
            </x-secondary-button>
        </div>
    </x-table.table-row>

    <livewire:admin.user.suspend-user-modal :user="$user" :key="'suspend-user-modal-' . $user->id" />
    <livewire:admin.user.unsuspend-user-modal :user="$user" :key="'unsuspend-user-modal-' . $user->id" />
    <livewire:admin.user.suspension-history-modal :user="$user" :key="'suspension-history-modal-' . $user->id" />
</div>
