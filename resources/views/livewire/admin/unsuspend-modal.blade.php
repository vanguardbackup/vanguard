<div>
    <x-modal name="unsuspend-user-modal-{{ $user->id }}" wire:key="unsuspend-user-modal-{{ $userId }}">
        <x-slot name="title">
            {{ __('Manage Suspension for ":name"', ['name' => $user->name ?? __('Unknown')]) }}
        </x-slot>
        <x-slot name="description">
            {{ __('Review the suspension details for this user.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-user-check-01</x-slot>

        <form wire:submit.prevent="unsuspendUser">
            <div class="modal-body">
                <div class="mb-4">
                    <h4 class="text-lg font-medium mb-3">{{ __('Action Summary') }}</h4>
                    <p>{{ __('You are about to unsuspend') }} <strong>{{ $user->name }}</strong>.</p>

                    @if ($user->hasSuspendedAccount())
                        @if ($activeSuspension)
                            <div class="mt-6">
                                <h4 class="text-lg font-medium mb-3">{{ __('Current Status') }}</h4>
                                <div class="alert alert-info p-4 rounded-md">
                                    <p class="font-bold mb-2">{{ __('Current Suspension Details:') }}</p>
                                    <div class="space-y-1 ml-2">
                                        <p>{{ __('Suspended on:') }} {{ $activeSuspension->suspended_at->format('M d, Y') }}</p>
                                        <p>{{ __('Reason:') }} {{ $activeSuspension->suspended_reason }}</p>
                                        @if ($activeSuspension->suspended_until)
                                            <p>{{ __('Scheduled until:') }} {{ $activeSuspension->suspended_until->format('M d, Y') }}</p>
                                        @else
                                            <p>{{ __('Suspended permanently') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="mt-6">
                            <h4 class="text-lg font-medium mb-3">{{ __('Current Status') }}</h4>
                            <div class="alert alert-warning p-4 rounded-md">
                                <p>{{ __('This user is not currently suspended.') }}</p>
                            </div>
                        </div>
                    @endif

                    @if ($pastSuspensions->count() > 0)
                        <div class="mt-8">
                            <h4 class="text-lg font-medium mb-3">{{ __('Suspension History') }}</h4>
                            <div class="overflow-y-auto max-h-60 rounded-md border border-gray-200">
                                @foreach ($pastSuspensions as $suspension)
                                    <div class="border-b border-gray-200 p-4 last:border-b-0">
                                        <div class="grid grid-cols-1 gap-1">
                                            <div><span class="font-medium mr-2">{{ __('Suspended:') }}</span>{{ $suspension->suspended_at->format('M d, Y') }}</div>
                                            <div><span class="font-medium mr-2">{{ __('Reason:') }}</span>{{ $suspension->suspended_reason }}</div>
                                            <div><span class="font-medium mr-2">{{ __('Lifted:') }}</span>{{ $suspension->lifted_at->format('M d, Y') }}</div>
                                            @if ($suspension->unsuspension_note)
                                                <div><span class="font-medium mr-2">{{ __('Note:') }}</span>{{ $suspension->unsuspension_note }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="mt-8">
                            <h4 class="text-lg font-medium mb-3">{{ __('Suspension History') }}</h4>
                            <div class="alert alert-info p-4 rounded-md">
                                <x-notice type="info">
                                    <p>{{ __('No prior suspensions found for this user.') }}</p>
                                </x-notice>
                            </div>
                        </div>
                    @endif

                    <div class="mt-8">
                        <h4 class="text-lg font-medium mb-3">{{ __('Provide Reason') }}</h4>
                        <x-input-label for="unsuspension-note" value="{{ __('Unsuspension Note') }}" />
                        <x-textarea
                            id="unsuspension-note"
                            wire:model="unsuspensionNote"
                            class="w-full"
                        />
                        <x-input-error :messages="$errors->get('unsuspensionNote')" class="mt-2" />
                        <x-input-explain>
                            {{ __('Optional note about why the suspension is being lifted.') }}
                        </x-input-explain>
                    </div>
                </div>
            </div>

            <div class="flex space-x-5 mt-6">
                <div class="w-4/6">
                    <x-primary-button
                        type="submit"
                        class="w-full"
                        centered
                    >
                        {{ __('Lift Suspension') }}
                    </x-primary-button>
                </div>
                <div class="ml-2 w-2/6">
                    <x-secondary-button
                        type="button"
                        class="w-full"
                        centered
                        x-on:click="$dispatch('close')"
                    >
                        {{ __('Cancel') }}
                    </x-secondary-button>
                </div>
            </div>
        </form>
    </x-modal>
</div>
