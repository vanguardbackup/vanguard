<?php

declare(strict_types=1);

namespace App\Livewire\Admin\IPChecker;

use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;

class IPCheckerPage extends Component
{
    public ?string $ipAddress;
    public bool $checked = false;
    /** @var array<int|string, mixed> */
    public array $results = [];
    public int $totalMatches = 0;
    public string $searchType = 'both'; // 'registration', 'login', or 'both'

    public function mount(?string $ipAddress = null): void
    {
        $this->ipAddress = $ipAddress ?? null;

        // If an IP was passed in the URL, perform a check immediately
        if ($this->ipAddress) {
            $this->check();
        }
    }

    public function render(): View
    {
        $user = request()->user();

        if (! $user || ! $user->isAdmin()) {
            abort(404);
        }

        return view('livewire.admin.ip-checker.page');
    }

    public function check(): void
    {
        $this->validate([
            'ipAddress' => ['required', 'string', 'ipv4'],
        ], [
            'ipAddress.required' => __('Please enter an IP address to analyze.'),
        ]);

        $query = User::query();

        // Apply search filters based on selected type
        if ($this->searchType === 'registration') {
            $query->where('registration_ip', $this->ipAddress);
        } elseif ($this->searchType === 'login') {
            $query->where('most_recent_login_ip', $this->ipAddress);
        } else {
            // Default: search both
            $query->where('registration_ip', $this->ipAddress)
                ->orWhere('most_recent_login_ip', $this->ipAddress);
        }

        $matchedUsers = $query->get();
        $this->totalMatches = $matchedUsers->count();

        // Format results for display
        $this->results = $matchedUsers->map(function ($user): array {
            return [
                'id' => $user->getAttribute('id'),
                'name' => $user->getAttribute('name'),
                'email' => $user->getAttribute('email'),
                'registration_match' => $user->getAttribute('registration_ip') === $this->ipAddress,
                'login_match' => $user->getAttribute('most_recent_login_ip') === $this->ipAddress,
                'created_at' => $user->getAttribute('created_at')->format('Y-m-d H:i:s'),
                'last_login' => $user->getAttribute('last_login_at') ? $user->getAttribute('last_login_at')->format('Y-m-d H:i:s') : 'Never',
            ];
        })->toArray();

        $this->checked = true;
    }

    public function updateSearchType(string $type): void
    {
        $this->searchType = $type;
    }

    public function clear(): void
    {
        $this->ipAddress = null;
        $this->checked = false;
        $this->results = [];
        $this->totalMatches = 0;
        $this->searchType = 'both';

        // Reset validation errors
        $this->resetValidation();
    }
}
