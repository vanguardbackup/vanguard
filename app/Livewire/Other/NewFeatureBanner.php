<?php

declare(strict_types=1);

namespace App\Livewire\Other;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Displays a banner for new features in the application.
 * Fetches the latest feature from cache and allows users to dismiss it.
 */
class NewFeatureBanner extends Component
{
    /**
     * The latest feature to display in the banner.
     *
     * @var array<string, string>|null
     */
    public ?array $latestFeature = null;

    /**
     * Initialize the component state.
     */
    public function mount(): void
    {
        $cachedFeature = Cache::get('latest_feature');

        if (is_array($cachedFeature)) {
            $this->latestFeature = $cachedFeature;
        } elseif ($cachedFeature !== null) {
            Log::warning('Unexpected data type for latest_feature in cache', ['type' => gettype($cachedFeature)]);
            $this->latestFeature = null;
        }
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.other.new-feature-banner');
    }

    /**
     * Dismiss the feature banner.
     */
    public function dismiss(): void
    {
        $this->latestFeature = null;
        $this->dispatch('featureDismissed');
    }
}
