<div>
    <x-modal id="unsuspend-user-modal-{{ $user->id }}" :title="__('Unsuspend User')">
        <div class="modal-body">
            <div class="mb-4">
                <p>You are about to unsuspend <strong>{{ $user->name }}</strong>.</p>

                @if ($user->hasSuspendedAccount())
                    @php
                        $activeSuspension = $user->suspensions()->whereNull('lifted_at')->latest()->first();
                    @endphp

                    @if ($activeSuspension)
                        <div class="alert alert-info">
                            <p><strong>Current Suspension Details:</strong></p>
                            <p>Suspended on: {{ $activeSuspension->suspended_at->format('M d, Y') }}</p>
                            <p>Reason: {{ $activeSuspension->suspended_reason }}</p>
                            @if ($activeSuspension->suspended_until)
                                <p>Scheduled until: {{ $activeSuspension->suspended_until->format('M d, Y') }}</p>
                            @else
                                <p>Suspended permanently</p>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="alert alert-warning">
                        This user is not currently suspended.
                    </div>
                @endif

                <div class="mb-3">
                    <label for="unsuspensionNote" class="form-label">{{ __('Note (Optional)') }}</label>
                    <textarea
                        id="unsuspensionNote"
                        wire:model.blur="unsuspensionNote"
                        class="form-control"
                        rows="3"
                        placeholder="{{ __('Add a private note about why you are lifting this suspension...') }}"
                    ></textarea>
                </div>

                <div class="form-check mb-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="notifyUserAboutUnsuspension"
                        wire:model.blur="notifyUserAboutUnsuspension">
                    <label class="form-check-label" for="notifyUserAboutUnsuspension">
                        {{ __('Notify user about unsuspension') }}
                    </label>
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <button
                type="button"
                class="btn btn-secondary"
                data-bs-dismiss="modal">
                {{ __('Cancel') }}
            </button>

            <button
                type="button"
                class="btn btn-primary"
                wire:click="unsuspendUser"
                wire:loading.attr="disabled"
                @if (!$user->hasSuspendedAccount()) disabled @endif>
                <span wire:loading wire:target="unsuspendUser" class="spinner-border spinner-border-sm me-1" role="status"></span>
                {{ __('Unsuspend User') }}
            </button>
        </x-slot>
    </x-modal>
</div>
