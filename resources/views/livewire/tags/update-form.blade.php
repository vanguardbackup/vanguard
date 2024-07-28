<div>
    <x-form-wrapper>
            <x-slot name="title">
                {{ __('Update a Tag') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Update a new tag that belongs to you.') }}
            </x-slot>
            <x-slot name="icon">
                heroicon-o-tag
            </x-slot>
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
                <div class="flex flex-col sm:flex-row sm:space-x-5 space-y-4 sm:space-y-0">
                    <div class="w-full sm:w-4/6">
                        <x-primary-button type="submit" class="w-full justify-center" centered action="submit" loadingText="Saving changes...">
                            {{ __('Save changes') }}
                        </x-primary-button>
                    </div>
                    <div class="w-full sm:w-2/6">
                        <a href="{{ route('tags.index') }}" wire:navigate class="block">
                            <x-secondary-button type="button" class="w-full justify-center" centered>
                                {{ __('Cancel') }}
                            </x-secondary-button>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </x-form-wrapper>
    <div class="flex justify-end mt-4">
        @livewire('tags.delete-tag-button', ['tag' => $tag])
    </div>
</div>
