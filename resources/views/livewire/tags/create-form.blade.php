<div>
    <x-form-wrapper>
        <form wire:submit="submit">
            <div class="mt-4">
                <x-input-label for="label" :value="__('Label')"/>
                <x-text-input id="label" class="block mt-1 w-full" type="text" wire:model="label" name="label"
                              autofocus/>
                <x-input-error :messages="$errors->get('label')" class="mt-2"/>
            </div>
            <div class="mt-4">
                <x-input-label for="description" :value="__('Description')"/>
                <x-textarea id="description" class="block mt-1 w-full" wire:model="description"
                            name="description"></x-textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2"/>
            </div>
            <div class="mt-6 max-w-3xl mx-auto">
                <div class="flex space-x-5">
                    <div class="w-4/6">
                        <x-primary-button type="submit" class="mt-4" centered action="submit">
                            {{ __('Save') }}
                        </x-primary-button>
                    </div>
                    <div class="w-2/6 ml-2">
                        <a href="{{ route('tags.index') }}" wire:navigate>
                            <x-secondary-button type="button" class="mt-4" centered>
                                {{ __('Cancel') }}
                            </x-secondary-button>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </x-form-wrapper>
</div>
