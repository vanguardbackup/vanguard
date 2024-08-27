<div>
    <x-form-wrapper>
        <x-slot name="title">
            {{ __('Create a Tag') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Add a new tag to your account.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-tags</x-slot>
        <form wire:submit="submit">
            <div class="mt-4">
                <x-input-label for="label" :value="__('Label')" />
                <x-text-input
                    id="label"
                    class="mt-1 block w-full"
                    type="text"
                    wire:model="label"
                    name="label"
                    autofocus
                />
                <x-input-error :messages="$errors->get('label')" class="mt-2" />
            </div>
            <div class="mt-4">
                <x-input-label for="description" :value="__('Description')" />
                <x-textarea
                    id="description"
                    class="mt-1 block w-full"
                    wire:model="description"
                    name="description"
                ></x-textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
            <div class="mx-auto mt-6 max-w-3xl">
                <div class="flex flex-col space-y-4 sm:flex-row sm:space-x-5 sm:space-y-0">
                    <div class="w-full sm:w-4/6">
                        <x-primary-button type="submit" class="w-full justify-center" centered action="submit">
                            {{ __('Save') }}
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
</div>
