<?php

use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * The current view state of the component.
     *
     * @var string
     */
    public string $currentView = 'list';

    /**
     * The name of the currently selected feature.
     *
     * @var string|null
     */
    public ?string $selectedFeature = null;

    /**
     * Initialize the component state.
     *
     * @return void
     */
    public function mount(): void
    {
        $this->currentView = $this->hasExperiments ? 'list' : 'no-content';
    }

    /**
     * Determine if there are any experiments available.
     *
     * @return bool
     */
    #[Computed]
    public function hasExperiments(): bool
    {
        return $this->experiments->isNotEmpty();
    }

    /**
     * Get all available experiments with their details.
     *
     * @return Collection
     */
    #[Computed]
    public function experiments(): Collection
    {
        return collect(Feature::all())->map(function ($feature) {
            return $this->getExperimentDetails($feature);
        });
    }

    /**
     * Toggle the active state of a feature for the current user.
     *
     * @param string $feature
     * @return void
     */
    public function toggleFeature(string $feature): void
    {
        $user = $this->getCurrentUser();

        Feature::for($user)->active($feature)
            ? Feature::for($user)->deactivate($feature)
            : Feature::for($user)->activate($feature);

        $this->dispatch('feature-toggled');
    }

    /**
     * Set the current view to the experiments list.
     *
     * @return void
     */
    public function viewExperiments(): void
    {
        $this->currentView = 'list';
    }

    /**
     * Open the feature details modal for a specific feature.
     *
     * @param string $feature
     * @return void
     */
    public function viewFeatureDetails(string $feature): void
    {
        $this->selectedFeature = $feature;
        $this->dispatch('open-modal', 'feature-details');
    }

    /**
     * Get the details of a specific experiment.
     *
     * @param string $feature
     * @return array
     */
    private function getExperimentDetails(string $feature): array
    {
        $user = $this->getCurrentUser();
        $featureDetails = Feature::definition($feature);

        return [
            'name' => $feature,
            'title' => $featureDetails['title'] ?? $feature,
            'description' => $featureDetails['description'] ?? '',
            'active' => Feature::active($feature),
            'enabled' => Feature::for($user)->active($feature),
        ];
    }

    /**
     * Get the current authenticated user.
     *
     * @return User
     */
    private function getCurrentUser(): User
    {
        return auth()->user();
    }
}; ?>

<div>
    @if ($currentView === 'no-content')
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('heroicon-o-beaker', 'h-16 w-16 text-primary-900 dark:text-white inline')
            </x-slot>
            <x-slot name="title">
                {{ __('No Experiments') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Sorry, there are no experiments available for your account at the moment.') }}
            </x-slot>
        </x-no-content>
    @elseif ($currentView === 'list')
        <x-form-wrapper>
            <x-slot name="title">{{ __('Feature Experiments') }}</x-slot>
            <x-slot name="description">
                {{ __('Explore and manage feature experiments for your account.') }}
            </x-slot>
            <x-slot name="icon">heroicon-o-beaker</x-slot>

            <div class="mb-8 p-6 bg-blue-50 dark:bg-blue-900/50 rounded-lg shadow-sm">
                <div class="flex items-center mb-4">
                    @svg('heroicon-o-light-bulb', 'w-8 h-8 text-blue-500 mr-3')
                    <h3 class="text-xl font-semibold text-blue-800 dark:text-blue-200">{{ __('What are Feature Experiments?') }}</h3>
                </div>
                <p class="text-blue-700 dark:text-blue-300 mb-4">
                    {{ __('Feature experiments are controlled rollouts of new functionalities or improvements to our application. They allow us to test new ideas, gather feedback, and ensure stability before full release. By participating in these experiments, you help shape the future of our product.') }}
                </p>
                <ul class="space-y-3">
                    <li class="flex items-start">
                        @svg('heroicon-o-check-circle', 'w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0')
                        <span class="text-blue-700 dark:text-blue-300">{{ __('Test new features before they\'re widely available') }}</span>
                    </li>
                    <li class="flex items-start">
                        @svg('heroicon-o-check-circle', 'w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0')
                        <span class="text-blue-700 dark:text-blue-300">{{ __('Provide valuable feedback to improve features') }}</span>
                    </li>
                    <li class="flex items-start">
                        @svg('heroicon-o-check-circle', 'w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0')
                        <span class="text-blue-700 dark:text-blue-300">{{ __('Customize your experience by enabling or disabling specific features') }}</span>
                    </li>
                </ul>
            </div>

            <div class="space-y-6">
                @foreach ($this->experiments as $experiment)
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg transition-all duration-200 overflow-hidden">
                        <div class="p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center mb-4 sm:mb-0">
                                    <div class="flex-shrink-0 mr-4">
                                        @svg('heroicon-o-beaker', 'w-10 h-10 text-gray-500 dark:text-gray-400')
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $experiment['title'] }}</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $experiment['enabled'] ? __('Enabled for you') : __('Disabled for you') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex justify-end sm:ml-4 sm:flex-shrink-0">
                                    <x-secondary-button
                                        wire:click="viewFeatureDetails('{{ $experiment['name'] }}')"
                                        class="mr-3"
                                    >
                                        {{ __('View Details') }}
                                    </x-secondary-button>
                                    <x-primary-button
                                        wire:click="toggleFeature('{{ $experiment['name'] }}')"
                                        class="{{ $experiment['enabled'] ? 'bg-green-600 hover:bg-green-700' : '' }}"
                                    >
                                        {{ $experiment['enabled'] ? __('Disable') : __('Enable') }}
                                    </x-primary-button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-form-wrapper>

        <x-modal name="feature-details" :show="$errors->isNotEmpty()" focusable>
            <x-slot name="title">
                {{ __('Feature Details') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Learn more about this feature experiment and manage its status.') }}
            </x-slot>
            <x-slot name="icon">
                heroicon-o-information-circle
            </x-slot>

            @if ($selectedFeature)
                @php
                    $feature = $this->experiments->firstWhere('name', $selectedFeature);
                @endphp
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $feature['title'] }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ $feature['description'] }}
                    </p>
                    <div class="flex items-center mb-4">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400 mr-2">{{ __('Status:') }}</span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $feature['enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $feature['enabled'] ? __('Enabled') : __('Disabled') }}
                        </span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400 mr-2">{{ __('Global Status:') }}</span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $feature['active'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $feature['active'] ? __('Active') : __('Inactive') }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Close') }}
                    </x-secondary-button>
                    <x-primary-button wire:click="toggleFeature('{{ $feature['name'] }}')">
                        {{ $feature['enabled'] ? __('Disable Feature') : __('Enable Feature') }}
                    </x-primary-button>
                </div>
            @endif
        </x-modal>
    @endif
</div>
