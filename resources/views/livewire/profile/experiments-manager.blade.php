<?php

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

/**
 * Experiment Management Component
 *
 * Manages feature experiments, allowing users to view, toggle, and provide feedback.
 * This component handles the UI and logic for interacting with feature flags.
 */
new class extends Component {
    /** @var string The current view state of the component */
    public string $currentView = 'list';

    /** @var string|null The currently selected experiment for detailed view */
    public ?string $selectedExperiment = null;

    /** @var string User's feedback text */
    public string $feedbackText = '';

    /** @var string User's email for feedback follow-up */
    public string $feedbackEmail = '';

    /** @var bool Controls the visibility of the feedback modal */
    public bool $showFeedbackModal = false;

    public function mount(): void
    {
        $this->currentView = $this->hasExperiments ? 'list' : 'no-content';
    }

    #[Computed]
    public function hasExperiments(): bool
    {
        return $this->experiments->isNotEmpty();
    }

    #[Computed]
    public function experiments(): Collection
    {
        return collect(Feature::all())->mapWithKeys(fn ($value, $key) => [$key => $this->getExperimentDetails($key)]);
    }

    /**
     * Toggles the state of an experiment for the current user.
     *
     * @param string $experiment The name of the experiment to toggle
     */
    public function toggleExperiment(string $experiment): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $isActive = Feature::for($user)->active($experiment);
        $experimentTitle = $this->getExperimentTitle($experiment);

        if ($isActive) {
            Feature::for($user)->deactivate($experiment);
            Toaster::success("Experiment '{$experimentTitle}' has been disabled.");
            $this->dispatch('experiment-toggled');
            return;
        }

        Feature::for($user)->activate($experiment);
        Toaster::success("Experiment '{$experimentTitle}' has been enabled.");
        $this->dispatch('experiment-toggled');
    }

    public function viewExperiments(): void
    {
        $this->currentView = 'list';
    }

    /**
     * Displays detailed information about a specific experiment.
     *
     * @param string $experiment The name of the experiment to view
     */
    public function viewExperimentDetails(string $experiment): void
    {
        $this->selectedExperiment = $experiment;
        $this->dispatch('open-modal', 'experiment-details');
    }

    public function openFeedbackModal(): void
    {
        $this->showFeedbackModal = true;
        $this->dispatch('close-modal', 'experiment-details');
        $this->dispatch('open-modal', 'experiment-feedback');
    }

    /**
     * Submits user feedback to the external feedback service.
     *
     * Validates input, sends the feedback, and handles the response.
     */
    public function submitFeedback(): void
    {
        $this->validateFeedback();

        try {
            $response = $this->sendFeedbackRequest();
            $this->handleFeedbackResponse($response);
        } catch (Exception $e) {
            $this->handleFeedbackException($e);
        }
    }

    /**
     * Validates the feedback form data.
     *
     */
    private function validateFeedback(): void
    {
        $this->validate([
            'selectedExperiment' => 'required|string|max:255',
            'feedbackText' => 'required|string|max:10000',
            'feedbackEmail' => 'nullable|email|max:255',
        ]);
    }

    /**
     * Sends the feedback request to the external service.
     *
     * @return Illuminate\Http\Client\Response
     */
    private function sendFeedbackRequest(): \Illuminate\Http\Client\Response
    {
        $feedbackServiceUrl = 'https://feedback.vanguardbackup.com/api/feedback';

        return Http::post($feedbackServiceUrl, [
            'experiment' => trim($this->selectedExperiment),
            'feedback' => trim($this->feedbackText),
            'php_version' => trim(phpversion()),
            'vanguard_version' => trim(obtain_vanguard_version()),
            'email_address' => $this->feedbackEmail ? trim($this->feedbackEmail) : null,
        ]);
    }

    /**
     * Handles the response from the feedback service.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @throws RuntimeException
     */
    private function handleFeedbackResponse(\Illuminate\Http\Client\Response $response): void
    {
        if (!$response->successful()) {
            $this->handleUnsuccessfulResponse($response);
        }

        if ($response->json('status') !== 'success') {
            throw new RuntimeException('Unexpected response status from feedback service');
        }

        Toaster::success($response->json('message', 'Your feedback has been successfully submitted. Thank you!'));
        $this->resetFeedbackForm();
    }

    /**
     * Handles unsuccessful responses from the feedback service.
     *
     * @param Illuminate\Http\Client\Response $response
     * @throws RuntimeException
     */
    private function handleUnsuccessfulResponse(\Illuminate\Http\Client\Response $response): void
    {
        if ($response->status() === 422) {
            $this->handleValidationErrors($response->json('errors'));
        }

        if ($response->status() === 429) {
            Toaster::error('Too many requests. Please try again later.');
        }

        throw new RuntimeException('Unexpected response from feedback service');
    }

    /**
     * Handles exceptions that occur during the feedback submission process.
     *
     * @param Exception $e
     */
    private function handleFeedbackException(\Exception $e): void
    {
        Log::error('Failed to submit feedback', ['error' => $e->getMessage()]);
        Toaster::error('We encountered an issue while submitting your feedback. Please try again later.');
    }

    private function resetFeedbackForm(): void
    {
        $this->feedbackText = '';
        $this->feedbackEmail = '';
        $this->showFeedbackModal = false;
        $this->dispatch('close-modal', 'experiment-feedback');
        $this->dispatch('open-modal', 'experiment-details');
    }

    /**
     * Handles validation errors from the feedback service.
     *
     * @param array $errors
     */
    private function handleValidationErrors(array $errors): void
    {
        $messages = [];
        foreach ($errors as $field => $errorMessages) {
            $messages[$field] = implode(' ', $errorMessages);
        }
        throw ValidationException::withMessages($messages);
    }

    public function closeFeedbackModal(): void
    {
        $this->showFeedbackModal = false;
        $this->dispatch('close-modal', 'experiment-feedback');
        $this->dispatch('open-modal', 'experiment-details');
    }

    /**
     * Retrieves detailed information about a specific experiment.
     *
     * @param string $experiment The name of the experiment
     * @return array
     */
    private function getExperimentDetails(string $experiment): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $isActive = Feature::active($experiment) ?? false;
        $isEnabled = Feature::for($user)->active($experiment) ?? false;
        $metadata = config("features.{$experiment}", []);

        return [
            'name' => $experiment,
            'title' => $metadata['title'] ?? $this->getExperimentTitle($experiment),
            'description' => $metadata['description'] ?? $this->getExperimentDescription($experiment),
            'group' => $metadata['group'] ?? 'Uncategorized',
            'icon' => $metadata['icon'] ?? 'hugeicons-test-tube-01',
            'active' => $isActive,
            'enabled' => $isEnabled,
        ];
    }

    /**
     * Generates a title for an experiment based on its name.
     *
     * @param string $experiment The name of the experiment
     * @return string
     */
    private function getExperimentTitle(string $experiment): string
    {
        return ucfirst(str_replace('-', ' ', $experiment));
    }

    /**
     * Generates a description for an experiment based on its name.
     *
     * @param string $experiment The name of the experiment
     * @return string
     */
    private function getExperimentDescription(string $experiment): string
    {
        return "This is the {$this->getExperimentTitle($experiment)} experiment.";
    }
};
?>

<div>
    @if ($currentView === 'no-content')
        <x-no-content withBackground>
            <x-slot name="icon">
                @svg('hugeicons-test-tube-01', 'h-16 w-16 text-primary-900 dark:text-white inline')
            </x-slot>
            <x-slot name="title">
                {{ __('No Active Experiments') }}
            </x-slot>
            <x-slot name="description">
                {{ __('There are currently no experiments available for your account.') }}
            </x-slot>
        </x-no-content>
    @elseif ($currentView === 'list')
        <x-form-wrapper>
            <x-slot name="title">{{ __('Experiments') }}</x-slot>
            <x-slot name="description">
                {{ __('Explore and manage experimental features for your account.') }}
            </x-slot>
            <x-slot name="icon">hugeicons-test-tube-01</x-slot>

            <div class="mb-8 p-6 bg-blue-50 dark:bg-blue-900/50 rounded-lg shadow-sm">
                <div class="flex items-center mb-4">
                    @svg('hugeicons-idea-01', 'w-8 h-8 text-blue-500 mr-3')
                    <h3 class="text-xl font-semibold text-blue-800 dark:text-blue-200">{{ __('About Feature Experiments') }}</h3>
                </div>
                <p class="text-blue-700 dark:text-blue-300 mb-4">
                    {{ __('Feature experiments allow us to introduce new functionalities and improvements in a controlled manner. By participating, you help shape the future of our product and enjoy early access to new features.') }}
                </p>
                <ul class="space-y-3">
                    <li class="flex items-start">
                        @svg('hugeicons-tick-01', 'w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0')
                        <span class="text-blue-700 dark:text-blue-300">{{ __('Access new features before wide release') }}</span>
                    </li>
                    <li class="flex items-start">
                        @svg('hugeicons-tick-01', 'w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0')
                        <span class="text-blue-700 dark:text-blue-300">{{ __('Provide valuable insights to enhance functionality') }}</span>
                    </li>
                    <li class="flex items-start">
                        @svg('hugeicons-tick-01', 'w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0')
                        <span class="text-blue-700 dark:text-blue-300">{{ __('Tailor your experience by managing experiment participation') }}</span>
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
                                                    {{ $experiment['enabled'] ? __('Active for your account') : __('Inactive for your account') }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex justify-end sm:ml-4 sm:flex-shrink-0">
                                            <x-secondary-button
                                                wire:click="viewExperimentDetails('{{ $experiment['name'] }}')"
                                                class="mr-3"
                                            >
                                                {{ __('More Info') }}
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
                {{ __('Experiment Information') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Learn more about this experiment and manage your participation.') }}
            </x-slot>
            <x-slot name="icon">
                hugeicons-test-tube-01
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
                    {{ $experiment['enabled'] ? __('Active') : __('Inactive') }}
                </span>
                    </div>
                    <div class="flex items-center mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400 mr-2">{{ __('Category:') }}</span>
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $experiment['group'] }}</span>
                    </div>
                </div>

                <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-md">
                    <p class="text-sm text-yellow-700 dark:text-yellow-200">
                        <span class="font-medium">{{ __('Note:') }}</span>
                        {{ __('You may need to refresh the page (F5 or Cmd/Ctrl + R) to see changes after activating or deactivating an experiment.') }}
                    </p>
                </div>

                <div class="mt-6 flex justify-between items-center">
                    <button
                        wire:click="openFeedbackModal"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-0"
                    >
                        @svg('hugeicons-comment-01', 'w-5 h-5 mr-2 -ml-1')
                        {{ __('Share Feedback') }}
                    </button>
                    <div class="flex space-x-3">
                        <x-secondary-button x-on:click="$dispatch('close')">
                            {{ __('Close') }}
                        </x-secondary-button>
                        <x-primary-button wire:click="toggleExperiment('{{ $experiment['name'] }}')">
                            {{ $experiment['enabled'] ? __('Deactivate') : __('Activate') }}
                        </x-primary-button>
                    </div>
                </div>
            @endif
        </x-modal>

        <x-modal name="experiment-feedback" :show="$showFeedbackModal" focusable>
            <x-slot name="title">
                {{ __('Share Your Thoughts') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Your feedback helps us improve this experimental feature.') }}
            </x-slot>
            <x-slot name="icon">
                hugeicons-comment-01
            </x-slot>

            <form wire:submit.prevent="submitFeedback">
                <div>
                    <div class="mt-4">
                        <x-input-label for="feedbackEmail" :value="__('Your Feedback')"/>
                        <x-textarea
                            autofocus
                            required
                            wire:model="feedbackText"
                            id="feedback"
                            class="mt-1 block w-full"
                            rows="4"
                            placeholder="{{ __('Tell us about your experience with this feature...') }}"
                        ></x-textarea>
                        <x-input-error :messages="$errors->get('feedbackText')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="feedbackEmail" :value="__('Contact Email (Optional)')"/>
                        <x-text-input
                            name="feedbackEmail"
                            wire:model="feedbackEmail"
                            id="feedbackEmail"
                            class="mt-1 block w-full"
                            type="email"
                        />
                        <x-input-explain>
                            {{ __('Provide your email if you\'d like us to follow up on your feedback.') }}
                        </x-input-explain>
                        <x-input-error :messages="$errors->get('feedbackEmail')" class="mt-2" />
                    </div>

                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Note: Your Vanguard version and PHP version will be included with your feedback to help us address any potential technical issues.') }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <x-secondary-button wire:click="closeFeedbackModal" type="button">
                        {{ __('Cancel') }}
                    </x-secondary-button>
                    <x-primary-button type="submit" action="submitFeedback" loadingText="Sending...">
                        {{ __('Submit Feedback') }}
                    </x-primary-button>
                </div>
            </form>
        </x-modal>
</div>
@endif
