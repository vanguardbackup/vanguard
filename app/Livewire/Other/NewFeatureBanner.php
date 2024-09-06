<?php

declare(strict_types=1);

namespace App\Livewire\Other;

use App\Models\UserDismissal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Displays a banner for new features in the application.
 *
 * This Livewire component fetches the latest feature from cache,
 * displays it to the user, and allows them to dismiss it. The dismissal
 * state is stored using the UserDismissal model to prevent the same feature
 * from being shown repeatedly.
 */
class NewFeatureBanner extends Component
{
    /**
     * The latest feature to be displayed in the banner.
     *
     * @var array<string, string>|null
     */
    public ?array $latestFeature = null;

    /**
     * Initialize the component state.
     *
     * This method is called when the component is first loaded.
     * It loads the latest feature from cache if available and not dismissed.
     */
    public function mount(): void
    {
        $this->loadLatestFeature();
    }

    /**
     * Render the component.
     *
     * @return View The view that represents the component
     */
    public function render(): View
    {
        return view('livewire.other.new-feature-banner');
    }

    /**
     * Dismiss the currently displayed feature.
     *
     * This method is called when the user chooses to dismiss the feature banner.
     * It creates a new UserDismissal record and clears the latestFeature property.
     */
    public function dismiss(): void
    {
        if ($this->latestFeature && Auth::check()) {
            $userId = Auth::id();
            if (is_int($userId)) {
                UserDismissal::dismiss(
                    $userId,
                    'feature',
                    $this->latestFeature['version'] ?? 'unknown'
                );
                $this->latestFeature = null;
            }
        }
        $this->dispatch('featureDismissed');
    }

    /**
     * Load the latest feature from cache if it hasn't been dismissed.
     *
     * This method checks the cache for the latest feature and compares it
     * against the user's dismissals. If the feature is new or hasn't been
     * dismissed, it's loaded into the component state.
     */
    private function loadLatestFeature(): void
    {
        $cachedFeature = Cache::get('latest_feature');

        if (! is_array($cachedFeature)) {
            if ($cachedFeature !== null) {
                Log::warning('Unexpected data type for latest_feature in cache', ['type' => gettype($cachedFeature)]);
            }

            return;
        }

        $currentVersion = $cachedFeature['version'] ?? 'unknown';

        if (Auth::check()) {
            $userId = Auth::id();
            if (is_int($userId) && ! UserDismissal::isDismissed($userId, 'feature', $currentVersion)) {
                $this->latestFeature = $cachedFeature;
            }
        }
    }
}
