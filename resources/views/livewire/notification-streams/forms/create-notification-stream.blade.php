<div>
    @section('title', __('Create Notification Stream'))
    <x-slot name="header">
        {{ __('Create Notification Stream') }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <x-form-wrapper>
            <form wire:submit="submit">
                <div class="mt-4 flex flex-col md:flex-row md:space-x-6 space-y-4 md:space-y-0">
                    <div class="w-full md:w-3/6">
                        <x-input-label for="label" :value="__('Label')"/>
                        <x-text-input id="label" class="block mt-1 w-full" type="text" wire:model="label" name="label"
                                      autofocus/>
                        <x-input-error :messages="$errors->get('label')" class="mt-2"/>
                    </div>
                    <div class="w-full md:w-3/6">
                        <x-input-label for="type" :value="__('Type')"/>
                        <x-select id="type" class="block mt-1 w-full" wire:model.live="type" name="type">
                            <option value="discord_webhook">Discord Webhook</option>
                            <option value="slack_webhook">Slack Webhook</option>
                            <option value="email">{{ __('Email') }}</option>
                        </x-select>
                        <x-input-error :messages="$errors->get('type')" class="mt-2"/>
                    </div>
                </div>
                @if ($type === \App\Models\NotificationStream::TYPE_DISCORD)
                    <div class="mt-4">
                        <x-input-label for="value" :value="__('Discord Webhook')"/>
                        <x-text-input id="value" class="block mt-1 w-full" type="text" wire:model="value" name="value"/>
                        <x-input-error :messages="$errors->get('value')" class="mt-2"/>
                    </div>
                @endif
                @if ($type === \App\Models\NotificationStream::TYPE_SLACK)
                    <div class="mt-4">
                        <x-input-label for="value" :value="__('Slack Webhook')"/>
                        <x-text-input id="value" class="block mt-1 w-full" type="text" wire:model="value" name="value"/>
                        <x-input-error :messages="$errors->get('value')" class="mt-2"/>
                    </div>
                @endif
                @if ($type === \App\Models\NotificationStream::TYPE_EMAIL)
                    <div class="mt-4">
                        <x-input-label for="value" :value="__('Email')"/>
                        <x-text-input id="value" class="block mt-1 w-full" type="email" wire:model="value" name="value"/>
                        <x-input-error :messages="$errors->get('value')" class="mt-2"/>
                    </div>
                @endif
                <div class="mt-6 max-w-3xl mx-auto">
                    <div class="flex flex-col sm:flex-row sm:space-x-5 space-y-4 sm:space-y-0">
                        <div class="w-full sm:w-4/6">
                            <x-primary-button type="submit" class="w-full justify-center" centered action="submit">
                                {{ __('Save') }}
                            </x-primary-button>
                        </div>
                        <div class="w-full sm:w-2/6">
                            <a href="{{ route('notification-streams.index') }}" wire:navigate class="block">
                                <x-secondary-button type="button" class="w-full justify-center" centered>
                                    {{ __('Cancel') }}
                                </x-secondary-button>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </x-form-wrapper>
        </div>
    </div>
</div>
