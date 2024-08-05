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
                    $fail('At least one permission must be selected.');
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
            'read' => [
                'name' => 'Read Access',
                'description' => 'Allows reading data from the API',
            ],
            'create' => [
                'name' => 'Create Access',
                'description' => 'Allows creating new resources via the API',
            ],
            'update' => [
                'name' => 'Update Access',
                'description' => 'Allows updating existing resources via the API',
            ],
            'delete' => [
                'name' => 'Delete Access',
                'description' => 'Allows deleting resources via the API',
            ],
            'manage-tags' => [
                'name' => 'Manage Tags',
                'description' => 'Allows managing of your tags',
            ],
            'view-backup-destinations' => [
                'name' => 'View Backup Destinations',
                'description' => 'Allows viewing backup destinations',
            ],
            'create-backup-destinations' => [
                'name' => 'Create Backup Destinations',
                'description' => 'Allows creating new backup destinations',
            ],
            'update-backup-destinations' => [
                'name' => 'Update Backup Destinations',
                'description' => 'Allows updating existing backup destinations',
            ],
            'delete-backup-destinations' => [
                'name' => 'Delete Backup Destinations',
                'description' => 'Allows deleting backup destinations',
            ],
            'view-remote-servers' => [
                'name' => 'View Remote Servers',
                'description' => 'Allows viewing remote servers',
            ],
            'create-remote-servers' => [
                'name' => 'Create Remote Servers',
                'description' => 'Allows creating new remote servers',
            ],
            'update-remote-servers' => [
                'name' => 'Update Remote Servers',
                'description' => 'Allows updating existing remote servers',
            ],
            'delete-remote-servers' => [
                'name' => 'Delete Remote Servers',
                'description' => 'Allows deleting remote servers',
            ],
            'view-notification-streams' => [
                'name' => 'View Notification Streams',
                'description' => 'Allows viewing notification streams',
            ],
            'create-notification-streams' => [
                'name' => 'Create Notification Streams',
                'description' => 'Allows creating new notification streams',
            ],
            'update-notification-streams' => [
                'name' => 'Update Notification Streams',
                'description' => 'Allows updating existing notification streams',
            ],
            'delete-notification-streams' => [
                'name' => 'Delete Notification Notification',
                'description' => 'Allows deleting notification streams',
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
