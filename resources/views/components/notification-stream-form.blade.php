@props(['form', 'submitLabel' => __('Save'), 'cancelRoute' => 'notification-streams.index'])
<x-form-wrapper>
    <x-slot name="title">
        {{ __('Notification Stream') }}
    </x-slot>
    <x-slot name="description">
        {{ __('Add or update a Notification Stream.') }}
    </x-slot>
    <x-slot name="icon">
        heroicon-o-bell
    </x-slot>
    <form wire:submit.prevent="submit">
        <div class="mt-4 flex flex-col md:flex-row md:space-x-6 space-y-4 md:space-y-0">
            <div class="w-full md:w-3/6">
                <x-input-label for="form.label" :value="__('Label')"/>
                <x-text-input id="form.label" class="block mt-1 w-full" type="text" wire:model.defer="form.label" name="form.label" autofocus/>
                @error('form.label') <x-input-error :messages="$message" class="mt-2"/> @enderror
            </div>
            <div class="w-full md:w-3/6">
                <x-input-label for="form.type" :value="__('Type')"/>
                <x-select id="form.type" class="block mt-1 w-full" wire:model.live="form.type" name="form.type">
                    @foreach ($form->availableTypes as $value => $label)
                        <option value="{{ $value }}">{{ __($label) }}</option>
                    @endforeach
                </x-select>
                @error('form.type') <x-input-error :messages="$message" class="mt-2"/> @enderror
            </div>
        </div>
        <div class="mt-4">
            <x-input-label for="form.value" :value="__($form->getValueLabel())"/>
            <x-text-input id="form.value" class="block mt-1 w-full" type="{{ $form->getValueInputType() }}" wire:model.defer="form.value" name="form.value"/>
            @error('form.value') <x-input-error :messages="$message" class="mt-2"/> @enderror
        </div>

        @foreach ($form->getAdditionalFieldsConfig() as $field => $config)
            <div class="mt-4">
                <x-input-label for="form.{{ $field }}" :value="__($config['label'])"/>
                <x-text-input id="form.{{ $field }}" class="block mt-1 w-full" type="text" wire:model.defer="form.{{ $field }}" name="form.{{ $field }}"/>
                @error('form.' . $field) <x-input-error :messages="$message" class="mt-2"/> @enderror
            </div>
        @endforeach

        <div class="mt-6">
            <x-form-section>
                {{ __('Notifications') }}
            </x-form-section>
            <div class="mt-4 mb-16 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="flex flex-col">
                    <div class="flex items-center justify-between">
                        <x-input-label for="form.success_notification" :value="__('Successful Backups')" class="mr-3"/>
                        <x-toggle
                            name="form.success_notification"
                            model="form.success_notification"
                            :aria-label="__('Notify on successful backups')"
                        />
                    </div>
                    <x-input-explain class="mt-2">
                        {{ __('Receive notifications when backups complete successfully.') }}
                    </x-input-explain>
                </div>

                <div class="flex flex-col">
                    <div class="flex items-center justify-between">
                        <x-input-label for="form.failed_notification" :value="__('Failed Backups')" class="mr-3"/>
                        <x-toggle
                            name="form.failed_notification"
                            model="form.failed_notification"
                            :aria-label="__('Notify on failed backups')"
                        />
                    </div>
                    <x-input-explain class="mt-2">
                        {{ __('Receive notifications when backups fail to complete.') }}
                    </x-input-explain>
                </div>
            </div>
        </div>
        <div class="mt-6 max-w-3xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:space-x-5 space-y-4 sm:space-y-0">
                <div class="w-full sm:w-4/6">
                    <x-primary-button type="submit" class="w-full justify-center" centered action="submit">
                        {{ $submitLabel }}
                    </x-primary-button>
                </div>
                <div class="w-full sm:w-2/6">
                    <a href="{{ route($cancelRoute) }}" wire:navigate class="block">
                        <x-secondary-button type="button" class="w-full justify-center" centered>
                            {{ __('Cancel') }}
                        </x-secondary-button>
                    </a>
                </div>
            </div>
        </div>
    </form>
</x-form-wrapper>
