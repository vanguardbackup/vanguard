<div>
    <x-modal name="suspension-history-modal-{{ $user->id }}" wire:key="suspension-history-modal-{{ $user->id }}">
        <x-slot name="title">
            {{ __('Suspension History for ":name"', ['name' => $user->name ?? __('Unknown')]) }}
        </x-slot>
        <x-slot name="description">
            {{ __('View the complete suspension history for this user.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-work-history</x-slot>
        <div class="modal-body">
            <div class="mb-6">
                <h4 class="mb-3 text-lg font-medium">{{ __('Current Status') }}</h4>
                @if ($user->hasSuspendedAccount())
                    <div class="alert alert-danger rounded-md p-4">
                        <p class="mb-2 font-bold">{{ __('Account Currently Suspended') }}</p>
                        @if ($activeSuspension)
                            <div class="mt-2 space-y-1">
                                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                    <div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ __('Suspended on:') }}
                                        </span>
                                        <p class="font-medium">
                                            {{ $activeSuspension->suspended_at->format('M d, Y') }}
                                        </p>
                                    </div>

                                    @if ($activeSuspension->suspended_until)
                                        <div>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ __('Scheduled until:') }}
                                            </span>
                                            <p class="font-medium">
                                                {{ $activeSuspension->suspended_until->format('M d, Y') }}
                                            </p>
                                        </div>
                                    @else
                                        <div>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ __('Duration:') }}
                                            </span>
                                            <p class="font-medium">{{ __('Permanent') }}</p>
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-2">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Reason:') }}</span>
                                    <p class="font-medium">{{ $activeSuspension->suspended_reason }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="alert alert-success rounded-md p-4">
                        <p class="font-bold">{{ __('Account Currently Active') }}</p>
                        <p class="mt-1">{{ __('This user is not currently suspended.') }}</p>
                    </div>
                @endif
            </div>

            <div class="mb-6">
                <h4 class="mb-3 text-lg font-medium">{{ __('Suspension Timeline') }}</h4>

                @if ($allSuspensions->count() > 0)
                    <div class="max-h-96 overflow-y-auto rounded-md border border-gray-200 dark:border-gray-600">
                        @foreach ($allSuspensions as $suspension)
                            <div
                                class="{{ $suspension->lifted_at === null ? 'bg-red-50' : '' }} border-b border-gray-200 p-4 last:border-b-0 dark:border-gray-600"
                            >
                                <div class="mb-2 flex items-start justify-between">
                                    <div class="font-bold">
                                        {{ $suspension->suspended_at->format('M d, Y') }}
                                        @if ($suspension->lifted_at === null)
                                            <span class="badge ml-2 rounded bg-red-500 px-2 py-1 text-xs text-white">
                                                {{ __('ACTIVE') }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Suspension #') }}{{ $suspension->id }}
                                    </div>
                                </div>

                                <div class="mt-3 grid grid-cols-1 gap-3">
                                    <div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ __('Suspended by:') }}
                                        </span>
                                        <p class="font-medium">
                                            {{ $suspension->suspendedByAdmin->name ?? __('System') }}
                                        </p>
                                    </div>

                                    <div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ __('Reason:') }}
                                        </span>
                                        <p class="font-medium">{{ $suspension->suspended_reason }}</p>
                                    </div>

                                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                        <div>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ __('Suspended on:') }}
                                            </span>
                                            <p class="font-medium">
                                                {{ $suspension->suspended_at->format('M d, Y H:i') }}
                                            </p>
                                        </div>

                                        @if ($suspension->suspended_until)
                                            <div>
                                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                                    {{ __('Scheduled until:') }}
                                                </span>
                                                <p class="font-medium">
                                                    {{ $suspension->suspended_until->format('M d, Y') }}
                                                </p>
                                            </div>
                                        @else
                                            <div>
                                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                                    {{ __('Duration:') }}
                                                </span>
                                                <p class="font-medium">{{ __('Permanent') }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($suspension->lifted_at)
                                        <div class="border-t border-gray-100 pt-2 dark:border-gray-400">
                                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                                <div>
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                                        {{ __('Lifted on:') }}
                                                    </span>
                                                    <p class="font-medium">
                                                        {{ $suspension->lifted_at->format('M d, Y H:i') }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                                        {{ __('Lifted by:') }}
                                                    </span>
                                                    <p class="font-medium">
                                                        {{ $suspension->liftedByAdmin->name ?? __('System') }}
                                                    </p>
                                                </div>
                                            </div>

                                            @if ($suspension->unsuspension_note)
                                                <div class="mt-2">
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                                        {{ __('Unsuspension Note:') }}
                                                    </span>
                                                    <p class="font-medium">{{ $suspension->unsuspension_note }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info rounded-md p-4">
                        <x-notice type="info">
                            <p>{{ __('No suspension history found for this user.') }}</p>
                        </x-notice>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">{{ __('Total suspensions:') }} {{ $allSuspensions->count() }}</p>
                </div>
                <div>
                    <x-secondary-button type="button" centered x-on:click="$dispatch('close')">
                        {{ __('Close') }}
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </x-modal>
</div>
