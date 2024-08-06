<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Sanctum\NewAccessToken;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * Manages API tokens for users, including granular permissions for backup destinations.
 *
 * @property-read User|Authenticatable $user
 */
class APITokenManager extends Component
{
    /** @var string The name of the new API token */
    public string $name = '';

    /** @var array<string, bool> The permissions for the new API token */
    public array $permissions = [];

    /** @var string|null The plain text value of the newly created token */
    public ?string $plainTextToken = null;

    /** @var int|null The ID of the API token being deleted */
    public ?int $apiTokenIdBeingDeleted = null;

    /**
     * Initialize the component
     */
    public function mount(): void
    {
        $this->resetPermissions();
    }

    /**
     * Define the validation rules for the component
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['required', 'array', 'min:1', function (string $attribute, array $value, callable $fail): void {
                if (array_filter($value) === []) {
                    $fail(__('At least one permission must be selected.'));
                }
            }],
        ];
    }

    /**
     * Create a new API token
     */
    public function createApiToken(): void
    {
        $this->resetErrorBag();

        $validated = $this->validate();

        $selectedPermissions = array_filter($validated['permissions']);

        $token = $this->user->createToken(
            $validated['name'],
            $selectedPermissions
        );

        $this->displayTokenValue($token);

        Toaster::success('API Token has been created.');

        $this->reset('name');
        $this->resetPermissions();

        $this->dispatch('created');
    }

    /**
     * Confirm the deletion of an API token
     *
     * @param  int  $tokenId  The ID of the token to be deleted
     */
    public function confirmApiTokenDeletion(int $tokenId): void
    {
        $this->apiTokenIdBeingDeleted = $tokenId;
        $this->dispatch('open-modal', 'confirm-api-token-deletion');
    }

    /**
     * Delete an API token
     */
    public function deleteApiToken(): void
    {
        if (! $this->apiTokenIdBeingDeleted) {
            return;
        }

        $this->user->tokens()->where('id', $this->apiTokenIdBeingDeleted)->delete();

        Toaster::success('API Token has been revoked.');

        $this->reset('apiTokenIdBeingDeleted');
        $this->user->load('tokens');
        $this->dispatch('close-modal', 'confirm-api-token-deletion');
    }

    /**
     * Get the authenticated user
     */
    public function getUserProperty(): Authenticatable|User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    /**
     * Render the component
     */
    public function render(): View
    {
        return view('livewire.profile.api-token-manager');
    }

    /**
     * Get the list of available permissions
     *
     * @return array<string, array<string, string>>
     */
    public function getPermissions(): array
    {
        return [
            'manage-tags' => [
                'name' => __('Manage Tags'),
                'description' => __('Allows managing of your tags'),
            ],
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
     * Reset the permissions array
     */
    public function resetPermissions(): void
    {
        $this->permissions = array_fill_keys(array_keys($this->getPermissions()), false);
    }

    /**
     * Validate permissions when updated
     *
     * @param  mixed  $value  The new value
     * @param  string|null  $key  The key of the updated permission
     *
     * @throws ValidationException
     */
    public function updatedPermissions(mixed $value, ?string $key = null): void
    {
        $this->validateOnly('permissions');
    }

    /**
     * Display the value of a newly created token
     *
     * @param  NewAccessToken  $newAccessToken  The newly created access token
     */
    protected function displayTokenValue(NewAccessToken $newAccessToken): void
    {
        $this->plainTextToken = explode('|', $newAccessToken->plainTextToken, 2)[1];
        $this->dispatch('close-modal', 'create-api-token');
        $this->dispatch('open-modal', 'api-token-value');
    }
}
