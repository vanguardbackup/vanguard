<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\User;
use App\Services\SanctumAbilitiesService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * Manages API tokens for users, including granular abilities for various operations.
 *
 * This component handles the creation, deletion, and viewing of API tokens,
 * as well as managing the abilities associated with each token.
 */
class APITokenManager extends Component
{
    /** @var string The name of the new API token */
    public string $name = '';

    /** @var array<string, bool> The abilities for the new API token */
    public array $abilities = [];

    /** @var array<string, array<string, array<string, string>>> The list of possible abilities */
    public array $availableAbilities = [];

    /** @var string|null The plain text value of the newly created token */
    public ?string $plainTextToken = null;

    /** @var int|null The ID of the API token being deleted */
    public ?int $apiTokenIdBeingDeleted = null;

    /** @var array<string, bool> The expanded state of ability groups */
    public array $expandedGroups = [];

    /** @var int|null The ID of the token whose abilities are being viewed */
    public ?int $viewingTokenId = null;

    /** @var Collection<int|string, PersonalAccessToken> The user's tokens */
    public Collection $tokens;

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
        /* @phpstan-ignore-next-line */
        $this->tokens = new Collection;
        $this->resetAbilities();
        $this->initializeExpandedGroups();
        $this->availableAbilities = $this->abilitiesService->getAbilities();
        $this->loadTokens();
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
        $this->loadTokens();

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

        /** @var User $user */
        $user = Auth::user();

        $user->tokens()->where('id', $this->apiTokenIdBeingDeleted)->delete();

        Toaster::success('API Token has been revoked.');

        $this->reset('apiTokenIdBeingDeleted');
        $this->loadTokens();
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
     * Render the component.
     */
    public function render(): View
    {
        Log::debug('APITokenManager rendering', [
            'abilitiesCount' => count($this->availableAbilities),
            'tokensCount' => $this->tokens->count(),
        ]);

        if ($this->availableAbilities === []) {
            Log::warning('No abilities available in APITokenManager');
        }

        return view('livewire.profile.api-token-manager');
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

    /**
     * Load the user's tokens.
     */
    private function loadTokens(): void
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

        $this->tokens = $personalAccessTokens;
    }
}
