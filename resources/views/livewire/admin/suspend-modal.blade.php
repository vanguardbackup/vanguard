<div>
    <x-modal name="suspend-user-modal-{{ $userId }}" wire:key="suspend-user-modal-{{ $userId }}">
        <x-slot name="title">
            {{ __('Suspend ":name"', ['name' => $user->name ?? __('Unknown')]) }}
        </x-slot>
        <x-slot name="description">
            {{ __('Suspend this user from this instance.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-user-block-01</x-slot>

        <x-notice type="warning" :text="__('Users are not notified when a suspension is applied to them.')" />

        <form wire:submit.prevent="suspendUser" x-data="{ permanentSuspend: @entangle('permanentlySuspend') }">
            <div class="mt-4">
                <x-input-label for="suspensionReason" :value="__('Suspension Reason')" />
                <x-select
                    id="suspensionReason"
                    name="suspensionReason"
                    class="mt-1 block w-full"
                    wire:model="suspensionReason"
                >
                    <option value="">
                        {{ __('Select a reason') }}
                    </option>
                    @foreach ($possibleSuspensionReasons as $reason)
                        <option value="{{ $reason }}">
                            {{ $reason }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('suspensionReason')" class="mt-2" />
                <x-input-explain>
                    {{ __('Please select a reason for suspending this user.') }}
                </x-input-explain>
            </div>

            <div class="mt-4">
                <div class="flex items-center py-1">
                    <x-checkbox
                        id="permanent-suspend-{{ $user->id }}"
                        wire:model="permanentlySuspend"
                        value="1"
                        name="permanentlySuspend"
                        x-model="permanentSuspend"
                    ></x-checkbox>
                    <div class="ml-2 flex-1">
                        {{ __('Permanently suspend') }}
                    </div>
                </div>
                <x-input-error :messages="$errors->get('permanentlySuspend')" class="mt-2" />
            </div>

            <div
                class="mt-4"
                x-show="!permanentSuspend"
                x-transition:enter="transition duration-300 ease-out"
                x-transition:enter-start="scale-95 transform opacity-0"
                x-transition:enter-end="scale-100 transform opacity-100"
                x-transition:leave="transition duration-200 ease-in"
                x-transition:leave-start="scale-100 transform opacity-100"
                x-transition:leave-end="scale-95 transform opacity-0"
            >
                <x-input-label for="suspendUntil" :value="__('Suspend Until')" />
                <x-text-input
                    id="suspendUntil"
                    name="suspendUntil"
                    type="date"
                    class="mt-1 block w-full"
                    wire:model="suspendUntil"
                />
                <x-input-error :messages="$errors->get('suspendUntil')" class="mt-2" />
                <x-input-explain>
                    {{ __('How long to ban this user for.') }}
                </x-input-explain>
            </div>

            <div class="mt-4">
                <x-input-label for="privateNote" :value="__('Private Note')" />
                <x-textarea wire:model="privateNote"></x-textarea>
                <x-input-error :messages="$errors->get('privateNote')" class="mt-2" />
                <x-input-explain>
                    {{ __('This private note is only shown to administrators.') }}
                </x-input-explain>
            </div>

            <div
                class="mt-4"
                x-show="!permanentSuspend"
                x-transition:enter="transition duration-300 ease-out"
                x-transition:enter-start="scale-95 transform opacity-0"
                x-transition:enter-end="scale-100 transform opacity-100"
                x-transition:leave="transition duration-200 ease-in"
                x-transition:leave-start="scale-100 transform opacity-100"
                x-transition:leave-end="scale-95 transform opacity-0"
            >
                <x-input-label for="notifyUserWhenSuspensionHasBeenLifted" :value="__('Email Notify')" />
                <x-toggle name="notifyUserWhenSuspensionHasBeenLifted" model="notifyUserWhenSuspensionHasBeenLifted" />
                <x-input-error :messages="$errors->get('notifyUserWhenSuspensionHasBeenLifted')" class="mt-2" />
                <x-input-explain>
                    {{ __('Should the user be notified when the ban is lifted? (Only applicable for temporary suspensions)') }}
                </x-input-explain>
            </div>

            <div class="mt-6 flex space-x-5">
                <div class="w-4/6">
                    <x-danger-button type="submit" class="w-full" centered>
                        {{ __('Suspend') }}
                    </x-danger-button>
                </div>
                <div class="ml-2 w-2/6">
                    <x-secondary-button type="button" class="w-full" centered x-on:click="$dispatch('close')">
                        {{ __('Cancel') }}
                    </x-secondary-button>
                </div>
            </div>
        </form>
    </x-modal>
</div>
