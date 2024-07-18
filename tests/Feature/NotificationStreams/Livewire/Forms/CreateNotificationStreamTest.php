<?php

declare(strict_types=1);

use App\Livewire\NotificationStreams\Forms\CreateNotificationStream;
use App\Models\User;
use Carbon\Carbon;
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
        ->set('form.label', $testData['label'])
        ->set('form.type', $testData['type'])
        ->set('form.value', $testData['value'])
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
        ->set('form.label', $testData['label'])
        ->set('form.type', $testData['type'])
        ->set('form.value', $testData['value'])
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
        ->set('form.label', $testData['label'])
        ->set('form.type', $testData['type'])
        ->set('form.value', $testData['value'])
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
        ->set('form.label', '')
        ->set('form.type', '')
        ->set('form.value', '')
        ->call('submit')
        ->assertHasErrors(['form.label', 'form.type', 'form.value']);
});

it('validates label max length', function (): void {
    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('form.label', str_repeat('a', 256))
        ->call('submit')
        ->assertHasErrors(['form.label' => 'max']);
});

it('validates email format', function (): void {
    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('form.label', 'Test Email')
        ->set('form.type', 'email')
        ->set('form.value', 'invalid-email')
        ->call('submit')
        ->assertHasErrors(['form.value']);

    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('form.label', 'Test Email')
        ->set('form.type', 'email')
        ->set('form.value', 'valid-email@example.com')
        ->call('submit')
        ->assertHasNoErrors(['form.value']);
});

it('validates Discord webhook format', function (): void {
    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('form.label', 'Test Discord Webhook')
        ->set('form.type', 'discord_webhook')
        ->set('form.value', 'https://invalid-discord-webhook.com')
        ->call('submit')
        ->assertHasErrors(['form.value']);
});

it('validates Slack webhook format', function (): void {
    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('form.label', 'Test Slack Webhook')
        ->set('form.type', 'slack_webhook')
        ->set('form.value', 'https://invalid-slack-webhook.com')
        ->call('submit')
        ->assertHasErrors(['form.value']);
});

it('clears validation errors when type changes', function (): void {
    $component = Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('form.label', 'Test Notification')
        ->set('form.type', 'email')
        ->set('form.value', 'invalid-email');

    $component->call('submit')
        ->assertHasErrors(['form.value']);

    $component->set('form.type', 'discord_webhook')
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
        ->set('form.label', $testData['label'])
        ->set('form.type', $testData['type'])
        ->set('form.value', $testData['value'])
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('notification_streams', [
        'user_id' => $this->user->id,
        'label' => $testData['label'],
        'type' => $testData['type'],
        'value' => $testData['value'],
    ]);

    $this->assertDatabaseMissing('notification_streams', [
        'user_id' => $user2->id,
        'label' => $testData['label'],
    ]);
});

it('submits successfully with email and notification preferences', function (): void {
    $testData = [
        'label' => 'Test Email Notification',
        'type' => 'email',
        'value' => 'notification-email@example.com',
        'success_notification' => true,
        'failed_notification' => false,
    ];

    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('form.label', $testData['label'])
        ->set('form.type', $testData['type'])
        ->set('form.value', $testData['value'])
        ->set('form.success_notification', $testData['success_notification'])
        ->set('form.failed_notification', $testData['failed_notification'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $this->assertDatabaseHas('notification_streams', [
        'user_id' => $this->user->id,
        'label' => $testData['label'],
        'type' => $testData['type'],
        'value' => $testData['value'],
    ]);

    $notificationStream = $this->user->notificationStreams()->latest()->first();
    $this->assertNotNull($notificationStream->receive_successful_backup_notifications);
    $this->assertNull($notificationStream->receive_failed_backup_notifications);
});

it('submits successfully with Discord webhook and notification preferences', function (): void {
    $testData = [
        'label' => 'Test Discord Webhook',
        'type' => 'discord_webhook',
        'value' => 'https://discord.com/api/webhooks/123456789/abcdefghijklmnop',
        'success_notification' => false,
        'failed_notification' => true,
    ];

    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('form.label', $testData['label'])
        ->set('form.type', $testData['type'])
        ->set('form.value', $testData['value'])
        ->set('form.success_notification', $testData['success_notification'])
        ->set('form.failed_notification', $testData['failed_notification'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $this->assertDatabaseHas('notification_streams', [
        'user_id' => $this->user->id,
        'label' => $testData['label'],
        'type' => $testData['type'],
        'value' => $testData['value'],
    ]);

    $notificationStream = $this->user->notificationStreams()->latest()->first();
    $this->assertNull($notificationStream->receive_successful_backup_notifications);
    $this->assertNotNull($notificationStream->receive_failed_backup_notifications);
});

it('submits successfully with Slack webhook and both notification preferences enabled', function (): void {
    $testData = [
        'label' => 'Test Slack Webhook',
        'type' => 'slack_webhook',
        'value' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
        'success_notification' => true,
        'failed_notification' => true,
    ];

    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('form.label', $testData['label'])
        ->set('form.type', $testData['type'])
        ->set('form.value', $testData['value'])
        ->set('form.success_notification', $testData['success_notification'])
        ->set('form.failed_notification', $testData['failed_notification'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $this->assertDatabaseHas('notification_streams', [
        'user_id' => $this->user->id,
        'label' => $testData['label'],
        'type' => $testData['type'],
        'value' => $testData['value'],
    ]);

    $notificationStream = $this->user->notificationStreams()->latest()->first();
    $this->assertNotNull($notificationStream->receive_successful_backup_notifications);
    $this->assertNotNull($notificationStream->receive_failed_backup_notifications);
});

it('submits successfully with both notification preferences disabled', function (): void {
    $testData = [
        'label' => 'Test Notification',
        'type' => 'email',
        'value' => 'test@example.com',
        'success_notification' => false,
        'failed_notification' => false,
    ];

    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('form.label', $testData['label'])
        ->set('form.type', $testData['type'])
        ->set('form.value', $testData['value'])
        ->set('form.success_notification', $testData['success_notification'])
        ->set('form.failed_notification', $testData['failed_notification'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $this->assertDatabaseHas('notification_streams', [
        'user_id' => $this->user->id,
        'label' => $testData['label'],
        'type' => $testData['type'],
        'value' => $testData['value'],
    ]);

    $notificationStream = $this->user->notificationStreams()->latest()->first();
    $this->assertNull($notificationStream->receive_successful_backup_notifications);
    $this->assertNull($notificationStream->receive_failed_backup_notifications);
});

it('sets correct datetime for enabled notification preferences', function (): void {
    $testData = [
        'label' => 'Test Notification',
        'type' => 'email',
        'value' => 'test@example.com',
        'success_notification' => true,
        'failed_notification' => true,
    ];

    Carbon::setTestNow(now());

    Livewire::actingAs($this->user)
        ->test(CreateNotificationStream::class)
        ->set('form.label', $testData['label'])
        ->set('form.type', $testData['type'])
        ->set('form.value', $testData['value'])
        ->set('form.success_notification', $testData['success_notification'])
        ->set('form.failed_notification', $testData['failed_notification'])
        ->call('submit')
        ->assertHasNoErrors();

    $notificationStream = $this->user->notificationStreams()->latest()->first();
    $this->assertTrue($notificationStream->receive_successful_backup_notifications);
    $this->assertTrue($notificationStream->receive_failed_backup_notifications);

    Carbon::setTestNow();
});
