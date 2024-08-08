<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\User;
use App\Services\SanctumAbilitiesService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Sanctum\NewAccessToken;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * Manages API tokens for users, including granular abilities for various operations.
 *
 * This component handles the creation, deletion, and viewing of API tokens,
 * as well as managing the abilities associated with each token.
 *
 * @property-read User|Authenticatable $user
 */
class APITokenManager extends Component
{
    /** @var string The name of the new API token */
    public string $name = '';

    /** @var array<string, bool> The abilities for the new API token */
    public array $abilities = [];

    /** @var string|null The plain text value of the newly created token */
    public ?string $plainTextToken = null;

    /** @var int|null The ID of the API token being deleted */
    public ?int $apiTokenIdBeingDeleted = null;

    /** @var array<string, bool> The expanded state of ability groups */
    public array $expandedGroups = [];

    /** @var int|null The ID of the token whose abilities are being viewed */
    public ?int $viewingTokenId = null;

    /** @var SanctumAbilitiesService Service for managing Sanctum abilities */
    private SanctumAbilitiesService $abilitiesService;

    /**
     * Boot the component and inject dependencies.
     */
    public function boot(SanctumAbilitiesService $sanctumAbilitiesService): void
    {
        $this->abilitiesService = $sanctumAbilitiesService;
    }

    /**
     * Initialize component state.
     */
    public function mount(): void
    {
        $this->resetAbilities();
        $this->initializeExpandedGroups();
    }

    /**
     * Define validation rules for the component.
     *
     * @return array<string, mixed>
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
     * Create a new API token with selected abilities.
     *
     * @throws ValidationException
     */
    public function createApiToken(): void
    {
        $this->resetErrorBag();

        $validated = $this->validate();

        $selectedAbilities = array_keys(array_filter($validated['abilities']));

        $token = $this->user->createToken(
            $validated['name'],
            $selectedAbilities
        );

        $this->displayTokenValue($token);

        Toaster::success('API Token has been created.');

        $this->reset('name');
        $this->resetAbilities();

        $this->dispatch('created');
    }

    /**
     * Confirm the deletion of an API token.
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

        $this->user->tokens()->where('id', $this->apiTokenIdBeingDeleted)->delete();

        Toaster::success('API Token has been revoked.');

        $this->reset('apiTokenIdBeingDeleted');
        $this->user->load('tokens');
        $this->dispatch('close-modal', 'confirm-api-token-deletion');
    }

    /**
     * View the abilities of a specific token.
     */
    public function viewTokenAbilities(int $tokenId): void
    {
        $this->viewingTokenId = $tokenId;
        $this->dispatch('open-modal', 'view-token-abilities');
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
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.profile.api-token-manager', [
            'availableAbilities' => $this->abilitiesService->getAbilities(),
            'tokens' => $this->user->tokens()->latest()->get(),
        ]);
    }

    /**
     * Reset the abilities array to its default state.
     */
    public function resetAbilities(): void
    {
        $this->abilities = array_fill_keys(
            array_keys(array_merge(...array_values($this->abilitiesService->getAbilities()))),
            false
        );
    }

    /**
     * Toggle the expanded state of an ability group.
     */
    public function toggleGroup(string $group): void
    {
        $this->expandedGroups[$group] = ! ($this->expandedGroups[$group] ?? false);
    }

    /**
     * Select all abilities.
     */
    public function selectAllAbilities(): void
    {
        $this->abilities = array_fill_keys(array_keys($this->abilities), true);
    }

    /**
     * Deselect all abilities.
     */
    public function deselectAllAbilities(): void
    {
        $this->abilities = array_fill_keys(array_keys($this->abilities), false);
    }

    /**
     * Validate abilities when updated.
     *
     * @throws ValidationException
     */
    public function updatedAbilities(mixed $value, ?string $key = null): void
    {
        $this->validateOnly('abilities');
    }

    /**
     * Display the plain text value of a newly created token.
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
        $this->expandedGroups = array_fill_keys(array_keys($this->abilitiesService->getAbilities()), false);
    }
}
