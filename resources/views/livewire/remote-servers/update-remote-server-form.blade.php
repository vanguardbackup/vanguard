<div>
    <x-form-wrapper>
        <form wire:submit="submit">
            <div class="mt-4">
                <x-input-label for="label" :value="__('Label')"/>
                <x-text-input id="label" class="block mt-1 w-full" type="text" wire:model="label" name="label"
                              autofocus
                              placeholder="{{ __('sunny-village') }}"/>
                <x-input-error :messages="$errors->get('label')" class="mt-2"/>
            </div>
            <div class="mt-4">
                <div class="flex space-x-4">
                    <div class="w-1/2">
                        <x-input-label for="host" :value="__('Host')"/>
                        <x-text-input id="host" class="block mt-1 w-full" type="text" wire:model="host"
                                      name="host"/>
                        <x-input-error :messages="$errors->get('host')" class="mt-2"/>
                    </div>
                    <div class="w-1/2">
                        <x-input-label for="port" :value="__('SSH Port')"/>
                        <x-text-input id="port" class="block mt-1 w-full" type="text" wire:model="port"
                                      name="port"/>
                        <x-input-error :messages="$errors->get('port')" class="mt-2"/>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <x-input-label for="username" :value="__('SSH Username')"/>
                <x-text-input id="username" class="block mt-1 w-full" type="text" wire:model="username"
                              name="username" placeholder="{{ __('user') }}"/>
                <x-input-error :messages="$errors->get('username')" class="mt-2"/>
            </div>
            <div class="mt-4">
                <x-input-label for="databasePassword" :value="__('Database Password')"/>
                <x-text-input id="databasePassword" class="block mt-1 w-full" type="password"
                              wire:model="databasePassword"
                              name="databasePassword"/>
                <x-input-error :messages="$errors->get('databasePassword')" class="mt-2"/>
                <x-input-explain>
                    {{ __('If you want to keep your current database password, leave the password field empty. If you want to change it, type the new password into the field.') }}                </x-input-explain>
            </div>
            <section>
                <div class="mt-6 max-w-3xl mx-auto">
                    <div class="flex space-x-5">
                        <div class="w-4/6">
                            <x-primary-button type="submit" class="mt-4" centered wire:loading.attr="disabled"
                                              wire:loading.class="opacity-50 cursor-not-allowed">

                                <div wire:loading wire:target="submit">
                                    <x-spinner class="mr-2 text-white dark:text-gray-900 h-4 w-4 inline"/>
                                    {{ __('Saving changes...') }}
                                </div>

                                <div wire:loading.remove wire:target="submit">
                                    {{ __('Save Changes') }}
                                </div>
                            </x-primary-button>
                        </div>
                        <div class="w-2/6 ml-2">
                            <a href="{{ route('remote-servers.index') }}" wire:navigate>
                                <x-secondary-button type="button" class="mt-4" centered>
                                    {{ __('Cancel') }}
                                </x-secondary-button>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </form>
    </x-form-wrapper>
</div>
