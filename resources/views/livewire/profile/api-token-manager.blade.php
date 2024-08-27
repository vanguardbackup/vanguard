<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
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
new class extends Component {
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

    /** @var string The selected expiration option for the new token */
    public string $expirationOption = '1_month';

    /** @var string|null The custom expiration date for the new token */
    public ?string $customExpirationDate = null;

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
            'abilities' => [
                'required',
                'array',
                'min:1',
                function (string $attribute, array $value, callable $fail): void {
                    if (array_filter($value) === []) {
                        $fail(__('At least one ability must be selected.'));
                    }
                },
            ],
            'expirationOption' => ['required', 'in:1_month,6_months,1_year,never,custom'],
            'customExpirationDate' => [
                'required_if:expirationOption,custom',
                'nullable',
                'date',
                'after:today',
                'before:' .
                now()
                    ->addYears(5)
                    ->format('Y-m-d'),
            ],
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

        $expiresAt = $this->getExpirationDate(
            $validated['expirationOption'],
            $validated['customExpirationDate'] ?? null,
        );

        $newAccessToken = $user->createToken($validated['name'], $selectedAbilities, $expiresAt);

        $this->displayTokenValue($newAccessToken);

        Toaster::success('API Token has been created.');

        $this->reset(['name', 'expirationOption', 'customExpirationDate']);
        $this->resetAbilities();

        $this->dispatch('created');
    }

    /**
     * Get the expiration date based on the selected option.
     *
     * @param string $option
     * @param string|null $customDate
     * @return Carbon|null
     */
    private function getExpirationDate(string $option, ?string $customDate): ?Carbon
    {
        return match ($option) {
            '1_month' => now()->addMonth(),
            '6_months' => now()->addMonths(6),
            '1_year' => now()->addYear(),
            'custom' => $customDate ? Carbon::parse($customDate) : null,
            default => null,
        };
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
        if (! $this->apiTokenIdBeingDeleted) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $user
            ->tokens()
            ->where('id', $this->apiTokenIdBeingDeleted)
            ->delete();

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
        $this->abilities = array_fill_keys(array_keys(array_merge(...array_values($this->getAbilities()))), false);
    }

    /**
     * Toggle the expanded state of an ability group.
     *
     * @param string $group
     */
    public function toggleGroup(string $group): void
    {
        $this->expandedGroups[$group] = ! ($this->expandedGroups[$group] ?? false);
    }

    /**
     * Select all available abilities.
     */
    public function selectAllAbilities(): void
    {
        Toaster::info('Selected all token abilities.');
        $this->abilities = array_fill_keys(array_keys($this->abilities), true);
    }

    /**
     * Deselect all abilities.
     */
    public function deselectAllAbilities(): void
    {
        Toaster::info('Deselected all token abilities.');
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

        $tokens = $user
            ->tokens()
            ->latest()
            ->get();

        /** @var Collection<int|string, PersonalAccessToken> $personalAccessTokens */
        $personalAccessTokens = $tokens->map(function ($token): PersonalAccessToken {
            return $token instanceof PersonalAccessToken ? $token : new PersonalAccessToken($token->getAttributes());
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
     * Get the expiration date for display.
     *
     * @param PersonalAccessToken $token
     * @return string
     */
    public function getExpirationDisplay(PersonalAccessToken $token): string
    {
        if (! $token->expires_at) {
            return __('Never');
        }

        $expiresAt = Carbon::parse($token->expires_at);
        return $expiresAt->diffForHumans([
            'parts' => 2,
            'join' => true,
            'short' => true,
        ]);
    }

    /**
     * Get the expiration status of a token.
     *
     * @param PersonalAccessToken $token
     * @return string
     */
    public function getExpirationStatus(PersonalAccessToken $token): string
    {
        if (! $token->expires_at) {
            return 'active';
        }

        $expiresAt = Carbon::parse($token->expires_at);
        $now = now();

        if ($expiresAt->isPast()) {
            return 'expired';
        } elseif ($expiresAt->diffInDays($now) <= 7) {
            return 'expiring-soon';
        } else {
            return 'active';
        }
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

    /**
     * Check if any ability is selected.
     *
     * @return bool
     */
    #[Computed]
    public function hasSelectedAbilities(): bool
    {
        return count(array_filter($this->abilities)) > 0;
    }
};

?>

<div
    x-data="{
        showCustomDatepicker: false,
        showNeverExpirationWarning: false,
        init() {
            this.$watch('$wire.expirationOption', (value) => {
                this.showCustomDatepicker = value === 'custom'
                this.showNeverExpirationWarning = value === 'never'
            })
        },
    }"
    class="space-y-6"
>
    @if ($this->tokens->isEmpty())
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('hugeicons-ticket-02', 'inline h-16 w-16 text-primary-900 dark:text-white')
            </x-slot>
            <x-slot name="title">
                {{ __('No API Tokens') }}
            </x-slot>
            <x-slot name="description">
                {{ __('You haven\'t created any API tokens yet.') }}
            </x-slot>
            <x-slot name="action">
                <x-primary-button
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'create-api-token')"
                    class="mt-4"
                >
                    {{ __('Create New Token') }}
                </x-primary-button>
            </x-slot>
        </x-no-content>
    @else
        <x-table.table-wrapper
            title="{{ __('API Tokens') }}"
            description="{{ __('Manage your API tokens for third-party access to Vanguard.') }}"
        >
            <x-slot name="icon">
                <x-hugeicons-ticket-02 class="h-6 w-6 text-primary-600 dark:text-primary-400" />
            </x-slot>
            <x-slot name="actions">
                <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-api-token')">
                    {{ __('Create New Token') }}
                </x-primary-button>
            </x-slot>
            <x-table.table-header>
                <div class="col-span-3">{{ __('Name') }}</div>
                <div class="col-span-3">{{ __('Last Used') }}</div>
                <div class="col-span-3">{{ __('Expiration') }}</div>
                <div class="col-span-3">{{ __('Actions') }}</div>
            </x-table.table-header>
            <x-table.table-body>
                @foreach ($this->tokens as $token)
                    <x-table.table-row>
                        <div class="col-span-12 flex items-center sm:col-span-3">
                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                {{ $token->name }}
                                @if ($token->isMobileToken())
                                    <span class="group relative ml-2">
                                        @svg('hugeicons-smart-phone-01', 'inline-block h-4 w-4 text-cyan-600 dark:text-cyan-400', ['aria-label' => __('Mobile Token'), 'role' => 'img'])
                                        <span
                                            class="pointer-events-none absolute bottom-full left-1/2 w-max -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-sm text-gray-100 opacity-0 transition-opacity group-hover:opacity-100"
                                        >
                                            {{ __('Mobile Token') }}
                                        </span>
                                    </span>
                                @endif
                            </p>
                        </div>
                        <div class="col-span-12 sm:col-span-3">
                            <span class="text-gray-600 dark:text-gray-300">
                                {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : __('Never') }}
                            </span>
                        </div>
                        <div class="col-span-12 sm:col-span-3">
                            <div class="flex justify-start sm:justify-end">
                                <span
                                    class="{{ $this->getExpirationStatus($token) === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : '' }} {{ $this->getExpirationStatus($token) === 'expiring-soon' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' : '' }} {{ $this->getExpirationStatus($token) === 'expired' ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' : '' }} inline-block whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium"
                                >
                                    {{ $this->getExpirationDisplay($token) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-span-12 flex justify-start space-x-2 sm:col-span-3 sm:justify-center">
                            <x-secondary-button wire:click="viewTokenAbilities({{ $token->id }})" iconOnly>
                                <span class="sr-only">
                                    {{ __('View Abilities') }}
                                </span>
                                @svg('hugeicons-eye', 'h-4 w-4')
                            </x-secondary-button>
                            <x-danger-button wire:click="confirmApiTokenDeletion({{ $token->id }})" iconOnly>
                                <span class="sr-only">
                                    {{ __('Delete Token') }}
                                </span>
                                @svg('hugeicons-delete-02', 'h-4 w-4')
                            </x-danger-button>
                        </div>
                    </x-table.table-row>
                @endforeach
            </x-table.table-body>
        </x-table.table-wrapper>
    @endif
    <!-- Create Token Modal -->
    <x-modal name="create-api-token" focusable>
        <x-slot name="title">
            {{ __('Create New API Token') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Generate a new API token with specific abilities and expiration.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-ticket-02</x-slot>
        <form wire:submit.prevent="createApiToken" class="space-y-6">
            <!-- Token Name -->
            <div>
                <x-input-label for="token_name" :value="__('Token Name')" />
                <x-text-input
                    id="token_name"
                    name="token_name"
                    type="text"
                    wire:model="name"
                    autofocus
                    class="mt-1 block w-full"
                    required
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Token Expiration -->
            <div>
                <x-input-label for="expiration_option" :value="__('Token Expiration')" />
                <x-select id="expiration_option" name="expiration_option" wire:model.live="expirationOption">
                    <option value="1_month">{{ __('1 Month') }}</option>
                    <option value="6_months">{{ __('6 Months') }}</option>
                    <option value="1_year">{{ __('1 Year') }}</option>
                    <option value="never">{{ __('Never') }}</option>
                    <option value="custom">{{ __('Custom') }}</option>
                </x-select>
                <x-input-error :messages="$errors->get('expirationOption')" class="mt-2" />
            </div>

            <!-- Custom Expiration Date -->
            @if ($expirationOption === 'custom')
                <div>
                    <x-input-label for="custom_expiration_date" :value="__('Custom Expiration Date')" />
                    <x-text-input
                        id="custom_expiration_date"
                        name="custom_expiration_date"
                        type="date"
                        wire:model="customExpirationDate"
                        class="mt-1 block w-full"
                        :min="date('Y-m-d', strtotime('+1 day'))"
                        :max="date('Y-m-d', strtotime('+5 years'))"
                    />
                    <x-input-error :messages="$errors->get('customExpirationDate')" class="mt-2" />
                </div>
            @endif

            <x-notice
                type="warning"
                title="{{ __('Warning: Token Never Expires') }}"
                text="{{ __('Creating a token that never expires can be a security risk. Only use this option if absolutely necessary.') }}"
                showIf="showNeverExpirationWarning"
            ></x-notice>

            <!-- Token Abilities -->
            <div>
                <x-input-label :value="__('Token Abilities')" class="mb-3" />
                <div class="mb-4 flex items-center justify-between">
                    <x-secondary-button wire:click="selectAllAbilities" type="button">
                        {{ __('Select All') }}
                    </x-secondary-button>
                    <x-secondary-button wire:click="deselectAllAbilities" type="button">
                        {{ __('Deselect All') }}
                    </x-secondary-button>
                </div>
                @if (! $this->hasSelectedAbilities)
                    <x-notice
                        type="error"
                        title="{{ __('Warning!') }}"
                        text="{{ __('At least one ability must be selected.') }}"
                        class="mb-4"
                    ></x-notice>
                @endif

                <div class="space-y-4">
                    @foreach ($this->availableAbilities as $group => $groupAbilities)
                        <div class="overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                            <button
                                type="button"
                                wire:click="toggleGroup('{{ $group }}')"
                                class="w-full bg-gray-100 px-4 py-2 text-left hover:bg-gray-200 focus:outline-none dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                            >
                                <span class="font-medium">{{ $group }}</span>
                                <span class="float-right">
                                    @if ($this->expandedGroups[$group])
                                        @svg('hugeicons-arrow-up-01', 'inline h-5 w-5')
                                    @else
                                        @svg('hugeicons-arrow-down-01', 'inline h-5 w-5')
                                    @endif
                                </span>
                            </button>
                            <div x-show="$wire.expandedGroups['{{ $group }}']" x-collapse>
                                <div class="space-y-4 p-4">
                                    @foreach ($groupAbilities as $key => $ability)
                                        <div class="flex items-center space-x-3">
                                            <x-toggle
                                                :name="'abilities.' . $key"
                                                :label="$ability['name']"
                                                :model="'abilities.' . $key"
                                                live
                                            />
                                            <div class="group relative">
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $ability['name'] }}
                                                    </p>
                                                </div>
                                                <div
                                                    class="absolute bottom-full left-0 z-50 mb-2 hidden w-48 rounded bg-gray-800 p-2 text-xs text-white group-hover:block"
                                                >
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
                <x-input-error :messages="$errors->get('abilities')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>
                <x-primary-button type="submit">
                    {{ __('Create') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Delete Token Confirmation Modal -->
    <x-modal name="confirm-api-token-deletion" focusable>
        <x-slot name="title">
            {{ __('Revoke API Token') }}
        </x-slot>

        <x-slot name="description">
            {{ __('You are about to revoke an API token. Please review the following information carefully before proceeding.') }}
        </x-slot>

        <x-slot name="icon">hugeicons-alert-02</x-slot>

        <div class="space-y-4">
            <p>
                {{ __('Revoking an API token has the following consequences:') }}
            </p>
            <ul class="ml-4 list-inside list-disc space-y-2">
                <li>
                    {{ __('Any applications or services using this token will immediately lose access.') }}
                </li>
                <li>
                    {{ __('You will need to generate a new token and update your applications if you wish to restore access.') }}
                </li>
                <li>
                    {{ __('This action cannot be undone. Once revoked, a token cannot be recovered.') }}
                </li>
            </ul>

            @isset($token)
                @if ($token->isMobileToken())
                    <x-notice type="warning" title="{{ __('Mobile Token Alert:') }}">
                        <p>
                            {{ __('This token is associated with a mobile device. Revoking it will log you out of the mobile application. You will need to log in again on your mobile device to regain access.') }}
                        </p>
                    </x-notice>
                @endif

                <p>
                    {{ __('Token Details:') }}
                    <br />
                    {{ __('Name:') }}
                    <strong>{{ $token->name }}</strong>
                    <br />
                    {{ __('Last used:') }}
                    <strong>
                        {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : __('Never') }}
                    </strong>
                </p>
            @endisset
        </div>

        <div class="mt-6 flex space-x-5">
            <div class="w-4/6">
                <x-danger-button
                    type="button"
                    wire:click="deleteApiToken"
                    class="w-full justify-center"
                    action="deleteApiToken"
                    loadingText="{{ __('Revoking...') }}"
                >
                    {{ __('Yes, Revoke This Token') }}
                </x-danger-button>
            </div>
            <div class="w-2/6">
                <x-secondary-button type="button" class="w-full justify-center" x-on:click="$dispatch('close')">
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
        <x-slot name="icon">hugeicons-ticket-02</x-slot>
        <div class="space-y-4">
            <div class="rounded-md bg-gray-100 p-4 dark:bg-gray-700">
                <code class="break-all text-sm text-gray-800 dark:text-gray-200" x-ref="tokenDisplay">
                    {{ $plainTextToken }}
                </code>
            </div>
            <div class="mt-6">
                <x-secondary-button
                    x-on:click="navigator.clipboard.writeText($refs.tokenDisplay.textContent); $dispatch('close')"
                    centered
                >
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
        <x-slot name="icon">hugeicons-ticket-02</x-slot>
        <div>
            @if ($viewingTokenId)
                @php
                    $token = $this->tokens->find($viewingTokenId);
                @endphp

                @if ($token)
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ $token->name }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Expires') }}:
                        <span
                            class="{{ $this->getExpirationStatus($token) === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : '' }} {{ $this->getExpirationStatus($token) === 'expiring-soon' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' : '' }} {{ $this->getExpirationStatus($token) === 'expired' ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' : '' }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                        >
                            {{ $this->getExpirationDisplay($token) }}
                        </span>
                    </p>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($this->getAbilities() as $group => $groupAbilities)
                            <div class="overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                                <div
                                    class="bg-gray-100 px-4 py-2 font-medium text-gray-900 dark:bg-gray-800 dark:text-gray-100"
                                >
                                    {{ $group }}
                                </div>
                                <ul class="space-y-2 p-4">
                                    @foreach ($groupAbilities as $key => $ability)
                                        <li class="flex items-center space-x-2">
                                            @if (in_array('*', $token->abilities, true) || in_array($key, $token->abilities, true))
                                                @svg('hugeicons-checkmark-circle-02', 'h-5 w-5 flex-shrink-0 text-green-500')
                                            @else
                                                @svg('hugeicons-cancel-circle', 'h-5 w-5 flex-shrink-0 text-red-500')
                                            @endif
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ $ability['name'] }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Token not found.') }}
                    </p>
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
