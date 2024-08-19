<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Pennant\Feature;
use Livewire\Volt\Volt;

beforeEach(function (): void {
    $this->user = User::factory()->create(['password' => Hash::make('password')]);
    $this->actingAs($this->user);
});

test('the component can be rendered', function (): void {
    Volt::test('profile.experiments-manager')
        ->assertOk();
});

test('the page can be visited by authenticated users', function (): void {
    $this->get(route('profile.experiments'))
        ->assertOk()
        ->assertSeeLivewire('profile.experiments-manager');
});

test('the page cannot be visited by guests', function (): void {
    Auth::logout();
    $this->get(route('profile.experiments'))
        ->assertRedirect('login');
    $this->assertGuest();
});

test('experiments are displayed correctly', function (): void {
    Feature::define('test-experiment', true);

    Volt::test('profile.experiments-manager')
        ->assertSee('Feature Experiments')
        ->assertSee('Test experiment');
});

test('experiment details can be viewed', function (): void {
    Feature::define('test-experiment', true);

    Volt::test('profile.experiments-manager')
        ->call('viewExperimentDetails', 'test-experiment')
        ->assertDispatched('open-modal', 'experiment-details')
        ->assertSee('Test experiment');
});

test('experiments can be toggled', function (): void {
    Feature::define('test-experiment', true);

    Volt::test('profile.experiments-manager')
        ->call('toggleExperiment', 'test-experiment')
        ->assertDispatched('experiment-toggled');

    $this->assertFalse(Feature::active('test-experiment'));

    Volt::test('profile.experiments-manager')
        ->call('toggleExperiment', 'test-experiment')
        ->assertDispatched('experiment-toggled');

    $this->assertTrue(Feature::active('test-experiment'));
});

test('feedback modal can be opened', function (): void {
    Feature::define('test-experiment', true);

    Volt::test('profile.experiments-manager')
        ->call('viewExperimentDetails', 'test-experiment')
        ->call('openFeedbackModal')
        ->assertSet('showFeedbackModal', true)
        ->assertDispatched('close-modal', 'experiment-details')
        ->assertDispatched('open-modal', 'experiment-feedback');
});

test('feedback can be submitted', function (): void {
    Feature::define('test-experiment', true);

    Http::fake([
        'https://feedback.vanguardbackup.com/api/feedback' => Http::response(['status' => 'success', 'message' => 'Feedback submitted successfully'], 200),
    ]);

    Volt::test('profile.experiments-manager')
        ->set('selectedExperiment', 'test-experiment')
        ->set('feedbackText', 'This is a test feedback')
        ->set('feedbackEmail', 'test@example.com')
        ->call('submitFeedback')
        ->assertSet('showFeedbackModal', false)
        ->assertDispatched('close-modal', 'experiment-feedback')
        ->assertDispatched('open-modal', 'experiment-details');
});

test('feedback submission handles validation errors', function (): void {
    Feature::define('test-experiment', true);

    Http::fake([
        'https://feedback.vanguardbackup.com/api/feedback' => Http::response(['errors' => ['feedbackText' => ['The feedback text field is required.']]], 422),
    ]);

    Volt::test('profile.experiments-manager')
        ->set('selectedExperiment', 'test-experiment')
        ->set('feedbackText', '')
        ->call('submitFeedback')
        ->assertHasErrors(['feedbackText' => 'required']);
});

test('feedback modal can be closed', function (): void {
    Volt::test('profile.experiments-manager')
        ->set('showFeedbackModal', true)
        ->call('closeFeedbackModal')
        ->assertSet('showFeedbackModal', false)
        ->assertDispatched('close-modal', 'experiment-feedback')
        ->assertDispatched('open-modal', 'experiment-details');
});

test('experiment details are correctly populated', function (): void {
    Feature::define('test-experiment', true);

    $testable = Volt::test('profile.experiments-manager');

    $testable->call('viewExperimentDetails', 'test-experiment');

    $selectedExperiment = $testable->get('selectedExperiment');
    $this->assertEquals('test-experiment', $selectedExperiment);
});
