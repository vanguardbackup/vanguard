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
                <h4 class="text-lg font-medium mb-3">{{ __('Current Status') }}</h4>
                @if ($user->hasSuspendedAccount())
                    <div class="alert alert-danger p-4 rounded-md">
                        <p class="font-bold mb-2">{{ __('Account Currently Suspended') }}</p>
                        @if ($activeSuspension)
                            <div class="space-y-1 mt-2">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Suspended on:') }}</span>
                                        <p class="font-medium">{{ $activeSuspension->suspended_at->format('M d, Y') }}</p>
                                    </div>
                                    @if ($activeSuspension->suspended_until)
                                        <div>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Scheduled until:') }}</span>
                                            <p class="font-medium">{{ $activeSuspension->suspended_until->format('M d, Y') }}</p>
                                        </div>
                                    @else
                                        <div>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Duration:') }}</span>
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
                    <div class="alert alert-success p-4 rounded-md">
                        <p class="font-bold">{{ __('Account Currently Active') }}</p>
                        <p class="mt-1">{{ __('This user is not currently suspended.') }}</p>
                    </div>
                @endif
            </div>

            <div class="mb-6">
                <h4 class="text-lg font-medium mb-3">{{ __('Suspension Timeline') }}</h4>

                @if ($allSuspensions->count() > 0)
                    <div class="overflow-y-auto max-h-96 rounded-md border border-gray-200 dark:border-gray-600">
                        @foreach ($allSuspensions as $suspension)
                            <div class="border-b border-gray-200 dark:border-gray-600 p-4 last:border-b-0 {{ $suspension->lifted_at === null ? 'bg-red-50' : '' }}">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="font-bold">
                                        {{ $suspension->suspended_at->format('M d, Y') }}
                                        @if ($suspension->lifted_at === null)
                                            <span class="badge bg-red-500 text-white px-2 py-1 text-xs rounded ml-2">
                                                {{ __('ACTIVE') }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Suspension #') }}{{ $suspension->id }}
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-3 mt-3">
                                    <div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Suspended by:') }}</span>
                                        <p class="font-medium">
                                            {{ $suspension->suspendedByAdmin->name ?? __('System') }}
                                        </p>
                                    </div>

                                    <div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Reason:') }}</span>
                                        <p class="font-medium">{{ $suspension->suspended_reason }}</p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Suspended on:') }}</span>
                                            <p class="font-medium">{{ $suspension->suspended_at->format('M d, Y H:i') }}</p>
                                        </div>

                                        @if ($suspension->suspended_until)
                                            <div>
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Scheduled until:') }}</span>
                                                <p class="font-medium">{{ $suspension->suspended_until->format('M d, Y') }}</p>
                                            </div>
                                        @else
                                            <div>
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Duration:') }}</span>
                                                <p class="font-medium">{{ __('Permanent') }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($suspension->lifted_at)
                                        <div class="pt-2 border-t border-gray-100 dark:border-gray-400">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <div>
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Lifted on:') }}</span>
                                                    <p class="font-medium">{{ $suspension->lifted_at->format('M d, Y H:i') }}</p>
                                                </div>
                                                <div>
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Lifted by:') }}</span>
                                                    <p class="font-medium">
                                                        {{ $suspension->liftedByAdmin->name ?? __('System') }}
                                                    </p>
                                                </div>
                                            </div>

                                            @if ($suspension->unsuspension_note)
                                                <div class="mt-2">
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Unsuspension Note:') }}</span>
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
                    <div class="alert alert-info p-4 rounded-md">
                        <x-notice type="info">
                            <p>{{ __('No suspension history found for this user.') }}</p>
                        </x-notice>
                    </div>
                @endif
            </div>

            <div class="flex justify-between items-center mt-6">
                <div>
                    <p class="text-sm text-gray-500">
                        {{ __('Total suspensions:') }} {{ $allSuspensions->count() }}
                    </p>
                </div>
                <div>
                    <x-secondary-button
                        type="button"
                        centered
                        x-on:click="$dispatch('close')"
                    >
                        {{ __('Close') }}
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </x-modal>
</div>
