@props(['form', 'submitLabel' => __('Save'), 'cancelRoute' => 'notification-streams.index'])

<x-form-wrapper>
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
            <x-input-label for="form.value" :value="__($form->availableTypes[$form->type] ?? 'Value')"/>
            <x-text-input id="form.value" class="block mt-1 w-full" type="{{ $form->type === 'email' ? 'email' : 'text' }}" wire:model.defer="form.value" name="form.value"/>
            @error('form.value') <x-input-error :messages="$message" class="mt-2"/> @enderror
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
