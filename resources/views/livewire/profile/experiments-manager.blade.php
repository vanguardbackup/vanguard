<?php

use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Log;

/**
 * Experiment Management Component
 *
 * Handles the display and management of feature experiments.
 * Allows users to view, enable, and disable experiments.
 */
new class extends Component
{
    /**
     * The current view state of the component.
     * Can be 'list' or 'no-content'.
     */
    public string $currentView = 'list';

    /**
     * The currently selected experiment for detailed view.
     */
    public ?string $selectedExperiment = null;

    public function mount(): void
    {
        $this->currentView = $this->hasExperiments ? 'list' : 'no-content';
    }

    #[Computed]
    public function hasExperiments(): bool
    {
        $hasExperiments = $this->experiments->isNotEmpty();
        Log::debug('Checking if has experiments', ['hasExperiments' => $hasExperiments]);
        return $hasExperiments;
    }

    #[Computed]
    public function experiments(): Collection
    {
        $allFeatures = Feature::all();

        $experiments = collect($allFeatures)
            ->mapWithKeys(function ($value, $key) {
                return [$key => $this->getExperimentDetails($key)];
            });

        Log::debug('Processed experiments', ['count' => $experiments->count(), 'experiments' => $experiments]);
        return $experiments;
    }

    public function toggleExperiment(string $experiment): void
    {
        $user = $this->getCurrentUser();
        $isActive = Feature::for($user)->active($experiment);

        Log::debug('Toggling experiment', ['experiment' => $experiment, 'currentState' => $isActive]);

        if ($isActive) {
            Toaster::success("Experiment '{$this->getExperimentTitle($experiment)}' has been disabled.");
            Feature::for($user)->deactivate($experiment);
        } else {
            Toaster::success("Experiment '{$this->getExperimentTitle($experiment)}' has been successfully enabled.");
            Feature::for($user)->activate($experiment);
        }

        $this->dispatch('experiment-toggled');
    }

    public function viewExperiments(): void
    {
        $this->currentView = 'list';
    }

    public function viewExperimentDetails(string $experiment): void
    {
        $this->selectedExperiment = $experiment;
        Log::debug('Viewing experiment details', ['experiment' => $experiment]);
        $this->dispatch('open-modal', 'experiment-details');
    }

    private function getExperimentDetails(string $experiment): array
    {
        $user = $this->getCurrentUser();
        $isActive = Feature::active($experiment) ?? false;
        $isEnabled = Feature::for($user)->active($experiment) ?? false;
        $metadata = config("features.{$experiment}", []);

        Log::debug('Getting experiment details', [
            'experiment' => $experiment,
            'isActive' => $isActive,
            'isEnabled' => $isEnabled,
            'metadata' => $metadata,
        ]);

        return [
            'name' => $experiment,
            'title' => $metadata['title'] ?? $this->getExperimentTitle($experiment),
            'description' => $metadata['description'] ?? $this->getExperimentDescription($experiment),
            'group' => $metadata['group'] ?? 'Uncategorized',
            'rolloutPercentage' => $metadata['rolloutPercentage'] ?? 0,
            'icon' => $metadata['icon'] ?? 'heroicon-o-beaker',
            'active' => $isActive,
            'enabled' => $isEnabled,
        ];
    }

    private function getExperimentTitle(string $experiment): string
    {
        return ucfirst(str_replace('-', ' ', $experiment));
    }

    private function getExperimentDescription(string $experiment): string
    {
        return "This is the {$this->getExperimentTitle($experiment)} experiment.";
    }

    private function getCurrentUser(): User
    {
        $user = auth()->user();
        Log::debug('Current user retrieved', ['userId' => $user->id]);
        return $user;
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
            <x-slot name="title">{{ __('Experiments') }}</x-slot>
            <x-slot name="description">
                {{ __('Explore and manage experiments for your account.') }}
            </x-slot>
            <x-slot name="icon">heroicon-o-beaker</x-slot>

            <div class="mb-8 p-6 bg-blue-50 dark:bg-blue-900/50 rounded-lg shadow-sm">
                <div class="flex items-center mb-4">
                    @svg('heroicon-o-light-bulb', 'w-8 h-8 text-blue-500 mr-3')
                    <h3 class="text-xl font-semibold text-blue-800 dark:text-blue-200">{{ __('What are Experiments?') }}</h3>
                </div>
                <p class="text-blue-700 dark:text-blue-300 mb-4">
                    {{ __('Experiments are controlled rollouts of new functionalities or improvements to our application. They allow us to test new ideas, gather feedback, and ensure stability before full release. By participating in these experiments, you help shape the future of our product.') }}
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
                        <span class="text-blue-700 dark:text-blue-300">{{ __('Customize your experience by enabling or disabling specific experiments') }}</span>
                    </li>
                </ul>
            </div>

            <div class="space-y-6">
                @foreach ($this->experiments->groupBy('group') as $group => $groupExperiments)
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ $group }}</h3>
                        @foreach ($groupExperiments as $experiment)
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg transition-all duration-200 overflow-hidden mb-4">
                                <div class="p-6">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-center mb-4 sm:mb-0">
                                            <div class="flex-shrink-0 mr-4">
                                                @svg($experiment['icon'], 'w-10 h-10 text-gray-500 dark:text-gray-400')
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
                                                wire:click="viewExperimentDetails('{{ $experiment['name'] }}')"
                                                class="mr-3"
                                            >
                                                {{ __('View Details') }}
                                            </x-secondary-button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </x-form-wrapper>

        <x-modal name="experiment-details" :show="$errors->isNotEmpty()" focusable>
            <x-slot name="title">
                {{ __('Experiment Details') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Learn more about this experiment and manage its status.') }}
            </x-slot>
            <x-slot name="icon">
                heroicon-o-information-circle
            </x-slot>

            @if ($selectedExperiment)
                @php
                    $experiment = $this->experiments->firstWhere('name', $selectedExperiment);
                @endphp
                <div class="mb-6">
                    <div class="flex items-center mb-4">
                        @svg($experiment['icon'], 'w-8 h-8 text-gray-500 dark:text-gray-400 mr-3')
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $experiment['title'] }}</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ $experiment['description'] }}
                    </p>
                    <div class="flex items-center mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400 mr-2">{{ __('Status:') }}</span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $experiment['enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $experiment['enabled'] ? __('Enabled') : __('Disabled') }}
                        </span>
                    </div>
                    <div class="flex items-center mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400 mr-2">{{ __('Group:') }}</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $experiment['group'] }}</span>
                    </div>
                </div>

                <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md">
                    <p class="text-sm text-yellow-700 dark:text-yellow-200">
                        <span class="font-medium">{{ __('Note:') }}</span>
                        {{ __('You may need to reload the page (F5 or Cmd/Ctrl + R) to see the effects of enabling or disabling an experiment.') }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Close') }}
                    </x-secondary-button>
                    <x-primary-button wire:click="toggleExperiment('{{ $experiment['name'] }}')">
                        {{ $experiment['enabled'] ? __('Disable Experiment') : __('Enable Experiment') }}
                    </x-primary-button>
                </div>
            @endif
        </x-modal>
    @endif
</div>
