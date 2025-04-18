<?php

declare(strict_types=1);

namespace App\Livewire\Admin\IPChecker;

use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;

/**
 * IP Checker Tool
 *
 * This component provides administrators with a tool to search users
 * by IP address.
 */
class IPCheckerPage extends Component
{
    /**
     * The IP address to search for
     */
    public ?string $ipAddress = null;

    /**
     * Whether a search has been performed
     */
    public bool $checked = false;

    /**
     * Collection of users matching the search criteria
     *
     * @var array<int|string, mixed>
     */
    public array $results = [];

    /**
     * Number of users matching the search
     */
    public int $totalMatches = 0;

    /**
     * Type of search to perform: 'registration', 'login', or 'both'
     */
    public string $searchType = 'both';

    /**
     * Initialize the component and perform search if IP provided in URL
     */
    public function mount(?string $ipAddress = null): void
    {
        $this->ipAddress = $ipAddress;

        if (! $this->ipAddress) {
            return;
        }

        $this->check();
    }

    /**
     * Render the component view and enforce admin-only access
     */
    public function render(): View
    {
        $user = request()->user();

        if (! $user || ! $user->isAdmin()) {
            abort(404);
        }

        return view('livewire.admin.ip-checker.page');
    }

    /**
     * Perform IP address search based on current criteria
     */
    public function check(): void
    {
        $this->validate([
            'ipAddress' => ['required', 'string', 'ipv4'],
        ], [
            'ipAddress.required' => __('Please enter an IP address to analyze.'),
        ]);

        $query = User::query();
        $this->applySearchFilters($query);

        $matchedUsers = $query->get();
        $this->totalMatches = $matchedUsers->count();
        $this->results = $this->formatResults($matchedUsers);
        $this->checked = true;

        $this->updateUrlParameter();
    }

    /**
     * Update the search type and maintain current state
     */
    public function updateSearchType(string $type): void
    {
        $this->searchType = $type;
    }

    /**
     * Reset all search parameters and clear results
     */
    public function clear(): void
    {
        $this->ipAddress = null;
        $this->checked = false;
        $this->results = [];
        $this->totalMatches = 0;
        $this->searchType = 'both';
        $this->resetValidation();

        $this->updateUrlParameter();
    }

    /**
     * Apply IP address filters to query based on selected search type
     */
    private function applySearchFilters($query): void
    {
        if ($this->searchType === 'registration') {
            $query->where('registration_ip', $this->ipAddress);

            return;
        }

        if ($this->searchType === 'login') {
            $query->where('most_recent_login_ip', $this->ipAddress);

            return;
        }

        // Default case: search both
        $query->where('registration_ip', $this->ipAddress)
            ->orWhere('most_recent_login_ip', $this->ipAddress);
    }

    /**
     * Format user data for display in results
     */
    private function formatResults($matchedUsers): array
    {
        return $matchedUsers->map(function ($user): array {
            return [
                'id' => $user->getAttribute('id'),
                'gravatar' => $user->gravatar(60),
                'name' => $user->getAttribute('name'),
                'email' => $user->getAttribute('email'),
                'registration_match' => $user->getAttribute('registration_ip') === $this->ipAddress,
                'login_match' => $user->getAttribute('most_recent_login_ip') === $this->ipAddress,
                'created_at' => $user->getAttribute('created_at')->diffForHumans(),
                'last_login' => $user->getAttribute('last_login_at')
                    ? $user->getAttribute('last_login_at')->diffForHumans()
                    : 'Never',
            ];
        })->toArray();
    }

    /**
     * Update browser URL to reflect current search state
     */
    private function updateUrlParameter(): void
    {
        $url = $this->ipAddress
            ? route('admin.ip-checker', ['ipAddress' => $this->ipAddress])
            : route('admin.ip-checker');

        $this->dispatch('urlChanged', ['url' => $url]);
    }
}
