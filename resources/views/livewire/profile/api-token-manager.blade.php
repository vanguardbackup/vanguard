<div>
    <!-- Create Token Modal -->
    <x-modal name="create-api-token" focusable>
        <x-slot name="title">
            {{ __('Create New API Token') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Generate a new API token with specific permissions.') }}
        </x-slot>
        <x-slot name="icon">
            heroicon-o-key
        </x-slot>
        <form wire:submit.prevent="createApiToken" class="space-y-6">
            <div>
                <x-input-label for="token_name" :value="__('Token Name')"/>
                <x-text-input id="token_name" name="token_name" type="text" wire:model="name"
                              class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2"/>
            </div>

            <div>
                <x-input-label :value="__('Token Permissions')" class="mb-3"/>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($this->getPermissions() as $key => $permission)
                        <div class="flex items-center space-x-3">
                            <x-toggle
                                :name="'permissions.' . $key"
                                :label="$permission['name']"
                                :model="'permissions.' . $key"
                                live
                            />
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $permission['description'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('permissions')" class="mt-2"/>
            </div>

            <div class="mt-6 max-w-3xl mx-auto">
                <div class="flex flex-col sm:flex-row sm:space-x-5 space-y-4 sm:space-y-0">
                    <div class="w-full sm:w-4/6">
                        <x-primary-button type="submit" class="w-full justify-center" centered action="createApiToken" ire:loading.attr="disabled">
                            {{ __('Save') }}
                        </x-primary-button>
                    </div>
                    <div class="w-full sm:w-2/6">
                        <x-secondary-button type="button" class="w-full justify-center" centered
                                            x-on:click="$dispatch('close')">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                    </div>
                </div>
            </div>
        </form>
    </x-modal>

    <!-- Token Value Modal -->
    <x-modal name="api-token-value" focusable>
        <x-slot name="title">
            {{ __('API Token Created') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Your new API token has been generated. Please copy it now, as it won\'t be shown again.') }}
        </x-slot>
        <x-slot name="icon">
            heroicon-o-key
        </x-slot>
        <div class="space-y-4">
            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-md">
                <code class="text-sm text-gray-800 dark:text-gray-200 break-all"
                      x-ref="tokenDisplay">{{ $plainTextToken }}</code>
            </div>
            <div class="mt-6">
                <x-secondary-button
                    x-on:click="navigator.clipboard.writeText($refs.tokenDisplay.textContent); $dispatch('close')" centered>
                    {{ __('Copy and Close') }}
                </x-secondary-button>
            </div>
        </div>
    </x-modal>

    @if ($this->user->tokens->count() === 0)
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('heroicon-o-key', 'h-16 w-16 text-primary-900 dark:text-white inline')
            </x-slot>
            <x-slot name="title">
                {{ __('No API Tokens') }}
            </x-slot>
            <x-slot name="description">
                {{ __('You haven\'t created any API tokens yet.') }}
            </x-slot>
            <x-slot name="action">
                <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-api-token')"
                                  class="mt-4">
                    {{ __('Create New Token') }}
                </x-primary-button>
            </x-slot>
        </x-no-content>
    @else
        <x-form-wrapper>
            <x-slot name="icon">
                heroicon-o-key
            </x-slot>
            <x-slot name="description">
                {{ __('Manage your API tokens for third-party access to Vanguard.') }}
            </x-slot>
            <x-slot name="title">
                {{ __('API Tokens') }}
            </x-slot>
            <div class="space-y-6">
                <div class="mt-6 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($this->user->tokens->sortByDesc('created_at') as $token)
                        <div class="py-4 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $token->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Created') }} {{ $token->created_at->diffForHumans() }}
                                    @if ($token->last_used_at)
                                        · {{ __('Last used') }} {{ $token->last_used_at->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                            <x-danger-button wire:click="confirmApiTokenDeletion({{ $token->id }})"
                                             wire:loading.attr="disabled" iconOnly>
                                @svg('heroicon-o-trash', 'w-4 h-4')
                            </x-danger-button>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-form-wrapper>
    @endif

    <!-- Delete Token Confirmation Modal -->
    <x-modal name="confirm-api-token-deletion" focusable>
        <x-slot name="title">
            {{ __('Delete API Token') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Are you sure you want to delete this API token? This action cannot be undone.') }}
        </x-slot>
        <x-slot name="icon">
            heroicon-o-trash
        </x-slot>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('Please be careful and ensure that you wish to remove your API token.') }}
        </p>
        <div class="flex space-x-5">
            <div class="w-4/6">
                <x-danger-button type="button" wire:click="deleteApiToken" class="mt-4" centered action="delete" loadingText="Removing...">
                    {{ __('Confirm Removal') }}
                </x-danger-button>
            </div>
            <div class="w-2/6 ml-2">
                <x-secondary-button type="button" class="mt-4" centered x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
</div>
