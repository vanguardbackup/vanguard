<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Volt\Component;
use Masmerise\Toaster\Toaster;

/**
 * API Token Manager Component
 *
 * Manages the creation, viewing, and deletion of API tokens for users.
 */
new class extends Component
{
    /** @var string The name of the new token being created */
    #[Modelable]
    public string $name = '';

    /** @var array The selected abilities for the new token */
    public array $abilities = [];

    /** @var array All available token abilities */
    public array $availableAbilities = [];

    /** @var string|null The plain text value of the newly created token */
    public ?string $plainTextToken = null;

    /** @var int|null The ID of the token being deleted */
    public ?int $apiTokenIdBeingDeleted = null;

    /** @var array Tracks which ability groups are expanded in the UI */
    public array $expandedGroups = [];

    /** @var int|null The ID of the token whose abilities are being viewed */
    public ?int $viewingTokenId = null;

    /**
     * Initialize the component state.
     */
    public function mount(): void
    {
        $this->availableAbilities = $this->getAbilities();
        $this->resetAbilities();
        $this->initializeExpandedGroups();
    }

    /**
     * Define validation rules for token creation.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['required', 'array', 'min:1', function (string $attribute, array $value, callable $fail): void {
                if (array_filter($value) === []) {
                    $fail(__('At least one ability must be selected.'));
                }
            }],
        ];
    }

    /**
     * Create a new API token.
     */
    public function createApiToken(): void
    {
        $this->resetErrorBag();

        $validated = $this->validate();

        $selectedAbilities = array_keys(array_filter($validated['abilities']));

        /** @var User $user */
        $user = Auth::user();

        $newAccessToken = $user->createToken(
            $validated['name'],
            $selectedAbilities
        );

        $this->displayTokenValue($newAccessToken);

        Toaster::success('API Token has been created.');

        $this->reset('name');
        $this->resetAbilities();

        $this->dispatch('created');
    }

    /**
     * Confirm the deletion of an API token.
     *
     * @param int $tokenId
     */
    public function confirmApiTokenDeletion(int $tokenId): void
    {
        $this->apiTokenIdBeingDeleted = $tokenId;
        $this->dispatch('open-modal', 'confirm-api-token-deletion');
    }

    /**
     * Delete the selected API token.
     */
    public function deleteApiToken(): void
    {
        if (!$this->apiTokenIdBeingDeleted) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $user->tokens()->where('id', $this->apiTokenIdBeingDeleted)->delete();

        Toaster::success('API Token has been revoked.');

        $this->reset('apiTokenIdBeingDeleted');
        $this->dispatch('close-modal', 'confirm-api-token-deletion');
    }

    /**
     * View the abilities of a specific token.
     *
     * @param int $tokenId
     */
    public function viewTokenAbilities(int $tokenId): void
    {
        $this->viewingTokenId = $tokenId;
        $this->dispatch('open-modal', 'view-token-abilities');
    }

    /**
     * Reset all abilities to false.
     */
    public function resetAbilities(): void
    {
        $this->abilities = array_fill_keys(
            array_keys(array_merge(...array_values($this->getAbilities()))),
            false
        );
    }

    /**
     * Toggle the expanded state of an ability group.
     *
     * @param string $group
     */
    public function toggleGroup(string $group): void
    {
        $this->expandedGroups[$group] = !($this->expandedGroups[$group] ?? false);
    }

    /**
     * Select all available abilities.
     */
    public function selectAllAbilities(): void
    {
        Toaster::info('Selected all abilities.');
        $this->abilities = array_fill_keys(array_keys($this->abilities), true);
    }

    /**
     * Deselect all abilities.
     */
    public function deselectAllAbilities(): void
    {
        Toaster::info('Deselected all abilities.');
        $this->abilities = array_fill_keys(array_keys($this->abilities), false);
    }

    /**
     * Validate abilities when updated.
     *
     * @param mixed $value
     * @param string|null $key
     */
    public function updatedAbilities(mixed $value, ?string $key = null): void
    {
        $this->validateOnly('abilities');
    }

    /**
     * Display the value of a newly created token.
     *
     * @param NewAccessToken $newAccessToken
     */
    protected function displayTokenValue(NewAccessToken $newAccessToken): void
    {
        $this->plainTextToken = explode('|', $newAccessToken->plainTextToken, 2)[1];
        $this->dispatch('close-modal', 'create-api-token');
        $this->dispatch('open-modal', 'api-token-value');
    }

    /**
     * Initialize the expanded state of ability groups.
     */
    private function initializeExpandedGroups(): void
    {
        $this->expandedGroups = array_fill_keys(array_keys($this->getAbilities()), false);
    }

    /**
     * Get all tokens for the authenticated user.
     *
     * @return Collection
     */
    #[Computed]
    public function tokens(): Collection
    {
        /** @var User $user */
        $user = Auth::user();

        $tokens = $user->tokens()->latest()->get();

        /** @var Collection<int|string, PersonalAccessToken> $personalAccessTokens */
        $personalAccessTokens = $tokens->map(function ($token): PersonalAccessToken {
            return $token instanceof PersonalAccessToken
                ? $token
                : new PersonalAccessToken($token->getAttributes());
        });

        return $personalAccessTokens;
    }

    /**
     * Get all available token abilities.
     *
     * @return array
     */
    #[Computed]
    public function getAbilities(): array
    {
        $abilities = [
            'General' => $this->getGeneralAbilities(),
            'Backup Destinations' => $this->getBackupDestinationAbilities(),
            'Remote Servers' => $this->getRemoteServerAbilities(),
            'Notification Streams' => $this->getNotificationStreamAbilities(),
            'Backup Tasks' => $this->getBackupTaskAbilities(),
        ];

        Log::debug('APITokenManager getAbilities', [
            'totalAbilities' => count($abilities),
            'abilityGroups' => array_keys($abilities),
        ]);

        return $abilities;
    }

    /**
     * Get general abilities.
     *
     * @return array
     */
    private function getGeneralAbilities(): array
    {
        return [
            'manage-tags' => [
                'name' => __('Manage Tags'),
                'description' => __('Allows managing of tags'),
            ],
        ];
    }

    /**
     * Get backup destination abilities.
     *
     * @return array
     */
    private function getBackupDestinationAbilities(): array
    {
        return [
            'view-backup-destinations' => [
                'name' => __('View Backup Destinations'),
                'description' => __('Allows viewing backup destinations'),
            ],
            'create-backup-destinations' => [
                'name' => __('Create Backup Destinations'),
                'description' => __('Allows creating new backup destinations'),
            ],
            'update-backup-destinations' => [
                'name' => __('Update Backup Destinations'),
                'description' => __('Allows updating existing backup destinations'),
            ],
            'delete-backup-destinations' => [
                'name' => __('Delete Backup Destinations'),
                'description' => __('Allows deleting backup destinations'),
            ],
        ];
    }

    /**
     * Get remote server abilities.
     *
     * @return array
     */
    private function getRemoteServerAbilities(): array
    {
        return [
            'view-remote-servers' => [
                'name' => __('View Remote Servers'),
                'description' => __('Allows viewing remote servers'),
            ],
            'create-remote-servers' => [
                'name' => __('Create Remote Servers'),
                'description' => __('Allows creating new remote servers'),
            ],
            'update-remote-servers' => [
                'name' => __('Update Remote Servers'),
                'description' => __('Allows updating existing remote servers'),
            ],
            'delete-remote-servers' => [
                'name' => __('Delete Remote Servers'),
                'description' => __('Allows deleting remote servers'),
            ],
        ];
    }

    /**
     * Get notification stream abilities.
     *
     * @return array
     */
    private function getNotificationStreamAbilities(): array
    {
        return [
            'view-notification-streams' => [
                'name' => __('View Notification Streams'),
                'description' => __('Allows viewing notification streams'),
            ],
            'create-notification-streams' => [
                'name' => __('Create Notification Streams'),
                'description' => __('Allows creating new notification streams'),
            ],
            'update-notification-streams' => [
                'name' => __('Update Notification Streams'),
                'description' => __('Allows updating existing notification streams'),
            ],
            'delete-notification-streams' => [
                'name' => __('Delete Notification Streams'),
                'description' => __('Allows deleting notification streams'),
            ],
        ];
    }

    /**
     * Get backup task abilities.
     *
     * @return array
     */
    private function getBackupTaskAbilities(): array
    {
        return [
            'view-backup-tasks' => [
                'name' => __('View Backup Tasks'),
                'description' => __('Allows viewing backup tasks'),
            ],
            'create-backup-tasks' => [
                'name' => __('Create Backup Tasks'),
                'description' => __('Allows creating new backup tasks'),
            ],
            'update-backup-tasks' => [
                'name' => __('Update Backup Tasks'),
                'description' => __('Allows updating existing backup tasks'),
            ],
            'delete-backup-tasks' => [
                'name' => __('Delete Backup Tasks'),
                'description' => __('Allows deleting backup tasks'),
            ],
            'run-backup-tasks' => [
                'name' => __('Run Backup Tasks'),
                'description' => __('Allows the running of backup tasks'),
            ],
        ];
    }
};

?>
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
            heroicon-o-code-bracket
        </x-slot>
        <form wire:submit.prevent="createApiToken" class="space-y-6">
            <div>
                <x-input-label for="token_name" :value="__('Token Name')"/>
                <x-text-input id="token_name" name="token_name" type="text" wire:model="name" autofocus
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
                    @foreach ($this->availableAbilities as $group => $groupAbilities)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
                            <button type="button" wire:click="toggleGroup('{{ $group }}')"
                                    class="w-full px-4 py-2 text-left bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none">
                                <span class="font-medium">{{ $group }}</span>
                                <span class="float-right">
                                        @if ($this->expandedGroups[$group])
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

    @if ($this->tokens->isEmpty())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('heroicon-o-code-bracket', 'h-16 w-16 text-primary-900 dark:text-white inline')
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
                heroicon-o-code-bracket
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
                                    <div class="flex items-center space-x-2">
                                        @if ($token->isMobileToken())
                                            <div title="{{ __('Token was used on a mobile device.') }}" class="flex items-center space-x-2 bg-cyan-100 dark:bg-cyan-600 rounded-full px-3 py-1">
                    <span class="text-cyan-600 dark:text-cyan-100">
                        @svg('heroicon-o-device-phone-mobile', 'w-5 h-5')
                    </span>
                                                <span class="text-cyan-600 dark:text-cyan-100 text-xs font-semibold">{{ __('Mobile') }}</span>
                                            </div>
                                        @endif
                                        <span>{{ $token->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $token->created_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : __('Never') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <x-secondary-button wire:click="viewTokenAbilities({{ $token->id }})" class="mr-2" iconOnly title="{{ __('View Abilities') }}">
                                        @svg('heroicon-o-eye', 'w-4 h-4')
                                    </x-secondary-button>
                                    <x-danger-button wire:click="confirmApiTokenDeletion({{ $token->id }})" wire:loading.attr="disabled" iconOnly title="{{ __('Revoke Token') }}">
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

    <!-- Token Value Modal -->
    <x-modal name="api-token-value" focusable>
        <x-slot name="title">
            {{ __('API Token Created') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Your new API token has been generated. Please copy it now, as it won\'t be shown again.') }}
        </x-slot>
        <x-slot name="icon">
            heroicon-o-code-bracket
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

    <!-- View Token Abilities Modal -->
    <x-modal name="view-token-abilities" focusable>
        <x-slot name="title">
            {{ __('Token Abilities') }}
        </x-slot>
        <x-slot name="description">
            {{ __('View the abilities assigned to this API token.') }}
        </x-slot>
        <x-slot name="icon">
            heroicon-o-code-bracket
        </x-slot>
        <div class="mt-4 space-y-4">
            @if ($viewingTokenId)
                @php
                    $token = $this->tokens->find($viewingTokenId);
                @endphp
                @if ($token)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @if (isset($this->availableAbilities) && is_array($this->availableAbilities))
                            @foreach ($this->availableAbilities as $group => $groupAbilities)
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
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $ability['name'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        @else
                            <p class="text-sm text-red-500 dark:text-red-400">{{ __('Available abilities are not defined or not in the expected format.') }}</p>
                        @endif
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
