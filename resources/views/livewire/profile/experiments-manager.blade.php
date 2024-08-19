<?php

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Experiment Management Component
 *
 * Handles the display and management of feature experiments.
 * Allows users to view, enable, disable experiments, and provide feedback.
 */
new class extends Component {
    /**
     * The current view state of the component.
     */
    public string $currentView = 'list';

    /**
     * The currently selected experiment for detailed view.
     */
    public ?string $selectedExperiment = null;

    /**
     * The feedback text entered by the user.
     */
    public string $feedbackText = '';

    /**
     * The email address entered by the user for feedback.
     */
    public string $feedbackEmail = '';

    /**
     * Controls the visibility of the feedback modal.
     */
    public bool $showFeedbackModal = false;

    /**
     * Initialize the component state.
     */
    public function mount(): void
    {
        $this->currentView = $this->hasExperiments ? 'list' : 'no-content';
    }

    /**
     * Determine if there are any experiments available.
     */
    #[Computed]
    public function hasExperiments(): bool
    {
        $hasExperiments = $this->experiments->isNotEmpty();
        Log::debug('Checking if has experiments', ['hasExperiments' => $hasExperiments]);
        return $hasExperiments;
    }

    /**
     * Retrieve and process all available experiments.
     */
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

    /**
     * Toggle the active state of an experiment for the current user.
     */
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

    /**
     * Switch to the experiments list view.
     */
    public function viewExperiments(): void
    {
        $this->currentView = 'list';
    }

    /**
     * Open the details modal for a specific experiment.
     */
    public function viewExperimentDetails(string $experiment): void
    {
        $this->selectedExperiment = $experiment;
        Log::debug('Viewing experiment details', ['experiment' => $experiment]);
        $this->dispatch('open-modal', 'experiment-details');
    }

    /**
     * Open the feedback modal and close the experiment details modal.
     */
    public function openFeedbackModal(): void
    {
        $this->showFeedbackModal = true;
        $this->dispatch('close-modal', 'experiment-details');
        $this->dispatch('open-modal', 'experiment-feedback');
    }

    /**
     * Submit user feedback for the selected experiment.
     */
    public function submitFeedback(): void
    {
        $this->validate([
            'selectedExperiment' => 'required|string|max:255',
            'feedbackText' => 'required|string|max:10000',
            'feedbackEmail' => 'nullable|email|max:255',
        ]);

        $feedbackServiceUrl = 'https://feedback.vanguardbackup.com/api/feedback';

        try {
            $response = Http::post($feedbackServiceUrl, [
                'experiment' => trim($this->selectedExperiment),
                'feedback' => trim($this->feedbackText),
                'php_version' => trim(phpversion()),
                'vanguard_version' => trim(obtain_vanguard_version()),
                'email_address' => $this->feedbackEmail ? trim($this->feedbackEmail) : null,
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                Toaster::success($response->json('message', 'Thank you for your feedback!'));
                $this->resetFeedbackForm();
            } elseif ($response->status() === 422) {
                $this->handleValidationErrors($response->json('errors'));
            } elseif ($response->status() === 429) {
                Toaster::error('Too many requests. Please try again later.');
            } else {
                throw new RuntimeException('Unexpected response from feedback service');
            }
        } catch (Exception $e) {
            Log::error('Failed to submit feedback', ['error' => $e->getMessage()]);
            Toaster::error('Failed to submit feedback. Please try again later.');
        }
    }

    /**
     * Reset the feedback form after successful submission.
     */
    private function resetFeedbackForm(): void
    {
        $this->feedbackText = '';
        $this->feedbackEmail = '';
        $this->showFeedbackModal = false;
        $this->dispatch('close-modal', 'experiment-feedback');
        $this->dispatch('open-modal', 'experiment-details');
    }

    /**
     * Handle validation errors from the API response.
     */
    private function handleValidationErrors(array $errors): void
    {
        $messages = [];
        foreach ($errors as $field => $errorMessages) {
            $messages[$field] = implode(' ', $errorMessages);
        }
        throw ValidationException::withMessages($messages);
    }

    /**
     * Close the feedback modal and reopen the experiment details modal.
     */
    public function closeFeedbackModal(): void
    {
        $this->showFeedbackModal = false;
        $this->dispatch('close-modal', 'experiment-feedback');
        $this->dispatch('open-modal', 'experiment-details');
    }

    /**
     * Retrieve detailed information about a specific experiment.
     */
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

    /**
     * Generate a human-readable title for an experiment.
     */
    private function getExperimentTitle(string $experiment): string
    {
        return ucfirst(str_replace('-', ' ', $experiment));
    }

    /**
     * Generate a default description for an experiment.
     */
    private function getExperimentDescription(string $experiment): string
    {
        return "This is the {$this->getExperimentTitle($experiment)} experiment.";
    }

    /**
     * Retrieve the current authenticated user.
     */
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
                        <span
                            class="text-blue-700 dark:text-blue-300">{{ __('Test new features before they\'re widely available') }}</span>
                    </li>
                    <li class="flex items-start">
                        @svg('heroicon-o-check-circle', 'w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0')
                        <span
                            class="text-blue-700 dark:text-blue-300">{{ __('Provide valuable feedback to improve features') }}</span>
                    </li>
                    <li class="flex items-start">
                        @svg('heroicon-o-check-circle', 'w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0')
                        <span
                            class="text-blue-700 dark:text-blue-300">{{ __('Customize your experience by enabling or disabling specific experiments') }}</span>
                    </li>
                </ul>
            </div>

            <div class="space-y-6">
                @foreach ($this->experiments->groupBy('group') as $group => $groupExperiments)
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ $group }}</h3>
                        @foreach ($groupExperiments as $experiment)
                            <div
                                class="border border-gray-200 dark:border-gray-600 rounded-lg transition-all duration-200 overflow-hidden mb-4">
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
                        <span
                            class="text-sm font-medium text-gray-600 dark:text-gray-400 mr-2">{{ __('Status:') }}</span>
                        <span
                            class="px-2 py-1 text-xs font-semibold rounded-full {{ $experiment['enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $experiment['enabled'] ? __('Enabled') : __('Disabled') }}
                        </span>
                    </div>
                    <div class="flex items-center mb-2">
                        <span
                            class="text-sm font-medium text-gray-600 dark:text-gray-400 mr-2">{{ __('Group:') }}</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $experiment['group'] }}</span>
                    </div>
                </div>

                <div
                    class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md">
                    <p class="text-sm text-yellow-700 dark:text-yellow-200">
                        <span class="font-medium">{{ __('Note:') }}</span>
                        {{ __('You may need to reload the page (F5 or Cmd/Ctrl + R) to see the effects of enabling or disabling an experiment.') }}
                    </p>
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <button
                        wire:click="openFeedbackModal"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-0"
                    >
                        @svg('heroicon-o-chat-bubble-left-right', 'w-5 h-5 mr-2 -ml-1')
                        {{ __('Give Feedback') }}
                    </button>
                    <div class="flex space-x-3">
                        <x-secondary-button x-on:click="$dispatch('close')">
                            {{ __('Close') }}
                        </x-secondary-button>
                        <x-primary-button wire:click="toggleExperiment('{{ $experiment['name'] }}')">
                            {{ $experiment['enabled'] ? __('Disable Experiment') : __('Enable Experiment') }}
                        </x-primary-button>
                    </div>
                </div>
            @endif
        </x-modal>

        <x-modal name="experiment-feedback" :show="$showFeedbackModal" focusable>
            <x-slot name="title">
                {{ __('Provide Feedback') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Share your thoughts on this experiment to help us improve.') }}
            </x-slot>
            <x-slot name="icon">
                heroicon-o-chat-bubble-left-right
            </x-slot>

            <form wire:submit.prevent="submitFeedback">
                <div class="mt-4">
                    <x-textarea
                        wire:model="feedbackText"
                        id="feedback"
                        class="mt-1 block w-full"
                        rows="4"
                        placeholder="{{ __('Your feedback here...') }}"
                    ></x-textarea>
                    <x-input-error :messages="$errors->get('feedbackText')" class="mt-2" />
                    <x-text-input
                        name="feedbackEmail"
                        wire:model="feedbackEmail"
                        id="feedbackEmail"
                        class="mt-3 block w-full"
                        type="email"
                        placeholder="{{ __('Your email address (optional)') }}"
                    />
                    <x-input-error :messages="$errors->get('feedbackEmail')" class="mt-2" />
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Note: Your Vanguard version and PHP version will be shared with this feedback. No other information will be included.') }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <x-secondary-button wire:click="closeFeedbackModal" type="button">
                        {{ __('Cancel') }}
                    </x-secondary-button>
                    <x-primary-button type="submit" action="submitFeedback" loadingText="Submitting...">
                        {{ __('Submit Feedback') }}
                    </x-primary-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
