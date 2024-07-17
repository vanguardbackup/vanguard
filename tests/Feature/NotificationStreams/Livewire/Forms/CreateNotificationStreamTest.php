<?php

declare(strict_types=1);

use App\Livewire\NotificationStreams\Forms\CreateNotificationStream;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('renders successfully', function (): void {
    Livewire::test(CreateNotificationStream::class)
        ->assertStatus(200);
});

it('submits successfully with email', function (): void {
    $testData = [
        'label' => 'Test Email Notification',
        'type' => 'email',
        'value' => 'notification-email@example.com',
    ];

    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('label', $testData['label'])
        ->set('type', $testData['type'])
        ->set('value', $testData['value'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $this->assertDatabaseHas('notification_streams', [
        'user_id' => $this->user->id,
        'label' => $testData['label'],
        'type' => $testData['type'],
        'value' => $testData['value'],
    ]);
});

it('submits successfully with Discord webhook', function (): void {
    $testData = [
        'label' => 'Test Discord Webhook',
        'type' => 'discord_webhook',
        'value' => 'https://discord.com/api/webhooks/123456789/abcdefghijklmnop',
    ];

    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('label', $testData['label'])
        ->set('type', $testData['type'])
        ->set('value', $testData['value'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $this->assertDatabaseHas('notification_streams', [
        'user_id' => $this->user->id,
        'label' => $testData['label'],
        'type' => $testData['type'],
        'value' => $testData['value'],
    ]);
});

it('submits successfully with Slack webhook', function (): void {
    $testData = [
        'label' => 'Test Slack Webhook',
        'type' => 'slack_webhook',
        'value' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
    ];

    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('label', $testData['label'])
        ->set('type', $testData['type'])
        ->set('value', $testData['value'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $this->assertDatabaseHas('notification_streams', [
        'user_id' => $this->user->id,
        'label' => $testData['label'],
        'type' => $testData['type'],
        'value' => $testData['value'],
    ]);
});

it('validates required fields', function (): void {
    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('label', '')
        ->set('type', '')
        ->set('value', '')
        ->call('submit')
        ->assertHasErrors(['label', 'type', 'value']);
});

it('validates label max length', function (): void {
    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('label', str_repeat('a', 256))
        ->call('submit')
        ->assertHasErrors(['label' => 'max']);
});

it('validates email format', function (): void {
    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('label', 'Test Email')
        ->set('type', 'email')
        ->set('value', 'invalid-email')
        ->call('submit')
        ->assertHasErrors(['value']);
});

it('validates Discord webhook format', function (): void {
    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('label', 'Test Discord Webhook')
        ->set('type', 'discord_webhook')
        ->set('value', 'https://invalid-discord-webhook.com')
        ->call('submit')
        ->assertHasErrors(['value']);
});

it('validates Slack webhook format', function (): void {
    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('label', 'Test Slack Webhook')
        ->set('type', 'slack_webhook')
        ->set('value', 'https://invalid-slack-webhook.com')
        ->call('submit')
        ->assertHasErrors(['value']);
});

it('updates validation message when type changes', function (): void {
    $component = Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('label', 'Test Notification')
        ->set('type', 'email')
        ->set('value', '');

    $component->call('submit')
        ->assertHasErrors(['value' => 'Please enter an email address.']);

    $component->set('type', 'discord_webhook')
        ->call('submit')
        ->assertHasErrors(['value' => 'Please enter a Discord webhook URL.']);

    $component->set('type', 'slack_webhook')
        ->call('submit')
        ->assertHasErrors(['value' => 'Please enter a Slack webhook URL.']);
});

it('clears validation errors when type changes', function (): void {
    $component = Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('label', 'Test Notification')
        ->set('type', 'email')
        ->set('value', 'invalid-email');

    $component->call('submit')
        ->assertHasErrors(['value']);

    $component->set('type', 'discord_webhook')
        ->assertHasNoErrors();
});

it('creates notification stream for authenticated user', function (): void {
    $user2 = User::factory()->create();

    $testData = [
        'label' => 'Test Notification',
        'type' => 'email',
        'value' => 'test@example.com',
    ];

    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('label', $testData['label'])
        ->set('type', $testData['type'])
        ->set('value', $testData['value'])
        ->call('submit');

    $this->assertDatabaseHas('notification_streams', [
        'user_id' => $this->user->id,
        'label' => $testData['label'],
    ]);

    $this->assertDatabaseMissing('notification_streams', [
        'user_id' => $user2->id,
        'label' => $testData['label'],
    ]);
});
