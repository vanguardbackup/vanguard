<div>
    <!-- Create Token Modal -->
    <x-modal name="create-api-token" focusable>
        <x-slot name="title">
            {{ __('Create New API Token') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Generate a new API token with specific abilities.') }}
        </x-slot>
        <x-slot name="icon">
            heroicon-o-key
        </x-slot>
        <form wire:submit.prevent="createApiToken" class="space-y-6">
            <div>
                <x-input-label for="token_name" :value="__('Token Name')"/>
                <x-text-input id="token_name" name="token_name" type="text" wire:model="name"
                              class="mt-1 block w-full" required/>
                <x-input-error :messages="$errors->get('name')" class="mt-2"/>
            </div>

            <div>
                <x-input-label :value="__('Token Abilities')" class="mb-3"/>
                <div class="mb-4 flex justify-between items-center">
                    <x-secondary-button wire:click="selectAllAbilities" type="button">
                        {{ __('Select All') }}
                    </x-secondary-button>
                    <x-secondary-button wire:click="deselectAllAbilities" type="button">
                        {{ __('Deselect All') }}
                    </x-secondary-button>
                </div>
                <div class="space-y-4">
                    @foreach ($availableAbilities as $group => $groupAbilities)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
                            <button type="button" wire:click="toggleGroup('{{ $group }}')"
                                    class="w-full px-4 py-2 text-left bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none">
                                <span class="font-medium">{{ $group }}</span>
                                <span class="float-right">
                                    @if ($expandedGroups[$group])
                                        @svg('heroicon-s-chevron-up', 'w-5 h-5 inline')
                                    @else
                                        @svg('heroicon-s-chevron-down', 'w-5 h-5 inline')
                                    @endif
                                </span>
                            </button>
                            <div x-show="$wire.expandedGroups['{{ $group }}']" x-collapse>
                                <div class="p-4 space-y-4">
                                    @foreach ($groupAbilities as $key => $ability)
                                        <div class="flex items-center space-x-3">
                                            <x-toggle
                                                :name="'abilities.' . $key"
                                                :label="$ability['name']"
                                                :model="'abilities.' . $key"
                                                live
                                            />
                                            <div class="relative group">
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $ability['name'] }}
                                                    </p>
                                                </div>
                                                <div
                                                    class="absolute left-0 bottom-full mb-2 w-48 bg-gray-800 text-white text-xs rounded p-2 hidden group-hover:block z-50">
                                                    {{ $ability['description'] }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('abilities')" class="mt-2"/>
            </div>

            <div class="mt-6 max-w-3xl mx-auto">
                <div class="flex flex-col sm:flex-row sm:space-x-5 space-y-4 sm:space-y-0">
                    <div class="w-full sm:w-4/6">
                        <x-primary-button type="submit" class="w-full justify-center" centered action="createApiToken"
                                          wire:loading.attr="disabled">
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
                    x-on:click="navigator.clipboard.writeText($refs.tokenDisplay.textContent); $dispatch('close')"
                    centered>
                    {{ __('Copy and Close') }}
                </x-secondary-button>
            </div>
        </div>
    </x-modal>

    @if ($this->tokens->isEmpty())
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
                <div class="py-2 px-4 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-l-4 border-blue-600 dark:border-blue-500 font-normal mb-6 rounded-r">
                    <div class="flex items-center">
                        @svg('heroicon-o-information-circle', 'h-5 w-5 flex-shrink-0 mr-2')
                        <span>
                            {{ __('Need help with API integration?') }}
                            <a href="https://docs.vanguardbackup.com/api/introduction" target="_blank" class="font-medium underline hover:text-blue-700 dark:hover:text-blue-300">
                                {{ __('Check our API documentation') }}
                            </a>
                        </span>
                    </div>
                </div>
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Name') }}
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Created') }}
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Last Used') }}
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($this->tokens as $token)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $token->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $token->created_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : __('Never') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <x-secondary-button wire:click="viewTokenAbilities({{ $token->id }})" class="mr-2"
                                                        iconOnly title="{{ __('View Abilities') }}">
                                        @svg('heroicon-o-eye', 'w-4 h-4')
                                    </x-secondary-button>
                                    <x-danger-button wire:click="confirmApiTokenDeletion({{ $token->id }})"
                                                     wire:loading.attr="disabled" iconOnly  title="{{ __('Revoke Token') }}">
                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                    </x-danger-button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
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
                <x-danger-button type="button" wire:click="deleteApiToken" class="mt-4" centered action="delete"
                                 loadingText="Removing...">
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

    <!-- View Token Abilities Modal -->
    <x-modal name="view-token-abilities" focusable>
        <x-slot name="title">
            {{ __('Token Abilities') }}
        </x-slot>
        <x-slot name="description">
            {{ __('View the abilities assigned to this API token.') }}
        </x-slot>
        <x-slot name="icon">
            heroicon-o-key
        </x-slot>
        <div class="mt-4 space-y-4">
            @if ($viewingTokenId)
                @php
                    $token = $this->user->tokens->find($viewingTokenId);
                @endphp
                @if ($token)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($availableAbilities as $group => $groupAbilities)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
                                <div class="bg-gray-100 dark:bg-gray-800 px-4 py-2 font-medium">
                                    {{ $group }}
                                </div>
                                <ul class="p-4 space-y-2">
                                    @foreach ($groupAbilities as $key => $ability)
                                        <li class="flex items-center space-x-2">
                                            @if (in_array($key, $token->abilities, true))
                                                @svg('heroicon-s-check-circle', 'w-5 h-5 text-green-500 flex-shrink-0')
                                            @else
                                                @svg('heroicon-s-x-circle', 'w-5 h-5 text-red-500 flex-shrink-0')
                                            @endif
                                            <span
                                                class="text-sm text-gray-700 dark:text-gray-300">{{ $ability['name'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Token not found.') }}</p>
                @endif
            @endif
        </div>
        <div class="mt-6">
            <x-secondary-button x-on:click="$dispatch('close')" centered>
                {{ __('Close') }}
            </x-secondary-button>
        </div>
    </x-modal>
</div>
