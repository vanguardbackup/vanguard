<?php

declare(strict_types=1);

use App\Livewire\NotificationStreams\Forms\UpdateNotificationStream;
use App\Models\NotificationStream;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

it('renders successfully', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->assertStatus(200);
});

it('updates successfully with email and notification preferences', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'value' => 'old-email@example.com',
        'receive_successful_backup_notifications' => null,
        'receive_failed_backup_notifications' => now(),
    ]);

    $newData = [
        'label' => 'Updated Email Notification',
        'type' => 'email',
        'value' => 'new-email@example.com',
        'success_notification' => true,
        'failed_notification' => false,
    ];

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.label', $newData['label'])
        ->set('form.type', $newData['type'])
        ->set('form.value', $newData['value'])
        ->set('form.success_notification', $newData['success_notification'])
        ->set('form.failed_notification', $newData['failed_notification'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $updatedStream = $notificationStream->fresh();
    expect($updatedStream)->toMatchArray([
        'id' => $notificationStream->id,
        'user_id' => $user->id,
        'label' => $newData['label'],
        'type' => $newData['type'],
        'value' => $newData['value'],
    ])
        ->and($updatedStream->receive_successful_backup_notifications)->not->toBeNull()
        ->and($updatedStream->receive_failed_backup_notifications)->toBeNull();
});

it('updates successfully with Discord webhook and notification preferences', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create([
        'user_id' => $user->id,
        'type' => 'discord_webhook',
        'value' => 'https://discord.com/api/webhooks/old',
        'receive_successful_backup_notifications' => now(),
        'receive_failed_backup_notifications' => now(),
    ]);

    $newData = [
        'label' => 'Updated Discord Webhook',
        'type' => 'discord_webhook',
        'value' => 'https://discord.com/api/webhooks/123456789/abcdefghijklmnop',
        'success_notification' => false,
        'failed_notification' => true,
    ];

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.label', $newData['label'])
        ->set('form.type', $newData['type'])
        ->set('form.value', $newData['value'])
        ->set('form.success_notification', $newData['success_notification'])
        ->set('form.failed_notification', $newData['failed_notification'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $updatedStream = $notificationStream->fresh();
    expect($updatedStream)->toMatchArray([
        'id' => $notificationStream->id,
        'user_id' => $user->id,
        'label' => $newData['label'],
        'type' => $newData['type'],
        'value' => $newData['value'],
    ])
        ->and($updatedStream->receive_successful_backup_notifications)->toBeNull()
        ->and($updatedStream->receive_failed_backup_notifications)->not->toBeNull();
});

it('updates successfully with Slack webhook and both notification preferences enabled', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create([
        'user_id' => $user->id,
        'type' => 'slack_webhook',
        'value' => 'https://hooks.slack.com/services/old',
        'receive_successful_backup_notifications' => null,
        'receive_failed_backup_notifications' => null,
    ]);

    $newData = [
        'label' => 'Updated Slack Webhook',
        'type' => 'slack_webhook',
        'value' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
        'success_notification' => true,
        'failed_notification' => true,
    ];

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.label', $newData['label'])
        ->set('form.type', $newData['type'])
        ->set('form.value', $newData['value'])
        ->set('form.success_notification', $newData['success_notification'])
        ->set('form.failed_notification', $newData['failed_notification'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $updatedStream = $notificationStream->fresh();
    expect($updatedStream)->toMatchArray([
        'id' => $notificationStream->id,
        'user_id' => $user->id,
        'label' => $newData['label'],
        'type' => $newData['type'],
        'value' => $newData['value'],
    ])
        ->and($updatedStream->receive_successful_backup_notifications)->not->toBeNull()
        ->and($updatedStream->receive_failed_backup_notifications)->not->toBeNull();
});

it('updates successfully with Teams webhook and both notification preferences enabled', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create([
        'user_id' => $user->id,
        'type' => 'teams_webhook',
        'value' => 'https://outlook.webhook.office.com/webhookb2/old',
        'receive_successful_backup_notifications' => null,
        'receive_failed_backup_notifications' => null,
    ]);

    $newData = [
        'label' => 'Updated Teams Webhook',
        'type' => 'teams_webhook',
        'value' => 'https://outlook.webhook.office.com/webhookb2/7a8b9c0d-1e2f-3g4h-5i6j-7k8l9m0n1o2p@a1b2c3d4-e5f6-g7h8-i9j0-k1l2m3n4o5p6/IncomingWebhook/qrstuvwxyz123456789/abcdef12-3456-7890-abcd-ef1234567890',
        'success_notification' => true,
        'failed_notification' => true,
    ];

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.label', $newData['label'])
        ->set('form.type', $newData['type'])
        ->set('form.value', $newData['value'])
        ->set('form.success_notification', $newData['success_notification'])
        ->set('form.failed_notification', $newData['failed_notification'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $updatedStream = $notificationStream->fresh();
    expect($updatedStream)->toMatchArray([
        'id' => $notificationStream->id,
        'user_id' => $user->id,
        'label' => $newData['label'],
        'type' => $newData['type'],
        'value' => $newData['value'],
    ])
        ->and($updatedStream->receive_successful_backup_notifications)->not->toBeNull()
        ->and($updatedStream->receive_failed_backup_notifications)->not->toBeNull();
});

it('updates successfully with both notification preferences disabled', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'value' => 'old-email@example.com',
        'receive_successful_backup_notifications' => now(),
        'receive_failed_backup_notifications' => now(),
    ]);

    $newData = [
        'label' => 'Updated Notification',
        'type' => 'email',
        'value' => 'new-email@example.com',
        'success_notification' => false,
        'failed_notification' => false,
    ];

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.label', $newData['label'])
        ->set('form.type', $newData['type'])
        ->set('form.value', $newData['value'])
        ->set('form.success_notification', $newData['success_notification'])
        ->set('form.failed_notification', $newData['failed_notification'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $updatedStream = $notificationStream->fresh();
    expect($updatedStream)->toMatchArray([
        'id' => $notificationStream->id,
        'user_id' => $user->id,
        'label' => $newData['label'],
        'type' => $newData['type'],
        'value' => $newData['value'],
    ])
        ->and($updatedStream->receive_successful_backup_notifications)->toBeNull()
        ->and($updatedStream->receive_failed_backup_notifications)->toBeNull();
});

it('sets correct datetime for enabled notification preferences', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'value' => 'old-email@example.com',
        'receive_successful_backup_notifications' => null,
        'receive_failed_backup_notifications' => null,
    ]);

    $newData = [
        'label' => 'Updated Notification',
        'type' => 'email',
        'value' => 'new-email@example.com',
        'success_notification' => true,
        'failed_notification' => true,
    ];

    Carbon::setTestNow(now());

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.label', $newData['label'])
        ->set('form.type', $newData['type'])
        ->set('form.value', $newData['value'])
        ->set('form.success_notification', $newData['success_notification'])
        ->set('form.failed_notification', $newData['failed_notification'])
        ->call('submit')
        ->assertHasNoErrors();

    $updatedStream = $notificationStream->fresh();
    expect($updatedStream->receive_successful_backup_notifications)->toBeTrue()
        ->and($updatedStream->receive_failed_backup_notifications)->toBeTrue();

    Carbon::setTestNow();
});

it('validates required fields', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.label', '')
        ->set('form.type', '')
        ->set('form.value', '')
        ->call('submit')
        ->assertHasErrors(['form.label', 'form.type', 'form.value']);
});

it('validates label max length', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.label', str_repeat('a', 256))
        ->call('submit')
        ->assertHasErrors(['form.label' => 'max']);
});

it('validates email format', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.type', 'email')
        ->set('form.value', 'invalid-email')
        ->call('submit')
        ->assertHasErrors(['form.value']);
});

it('validates Discord webhook format', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.type', 'discord_webhook')
        ->set('form.value', 'https://invalid-discord-webhook.com')
        ->call('submit')
        ->assertHasErrors(['form.value']);
});

it('validates Slack webhook format', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.type', 'slack_webhook')
        ->set('form.value', 'https://invalid-slack-webhook.com')
        ->call('submit')
        ->assertHasErrors(['form.value']);
});

it('validates Teams webhook format', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.type', 'teams_webhook')
        ->set('form.value', 'https://invalid-teams-webhook.com')
        ->call('submit')
        ->assertHasErrors(['form.value']);
});

it('allows authorized users to update', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    $newData = [
        'label' => 'Authorized Update',
        'type' => 'email',
        'value' => 'authorized@example.com',
        'success_notification' => true,
        'failed_notification' => false,
    ];

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.label', $newData['label'])
        ->set('form.type', $newData['type'])
        ->set('form.value', $newData['value'])
        ->set('form.success_notification', $newData['success_notification'])
        ->set('form.failed_notification', $newData['failed_notification'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $updatedStream = $notificationStream->fresh();
    expect($updatedStream)->toMatchArray([
        'id' => $notificationStream->id,
        'user_id' => $user->id,
        'label' => $newData['label'],
        'type' => $newData['type'],
        'value' => $newData['value'],
    ])
        ->and($updatedStream->receive_successful_backup_notifications)->not->toBeNull()
        ->and($updatedStream->receive_failed_backup_notifications)->toBeNull();
});

it('prevents unauthorized users from updating', function (): void {
    $owner = User::factory()->create();
    $unauthorizedUser = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $owner->id]);

    Livewire::actingAs($unauthorizedUser)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->assertStatus(403);
});

it('updates successfully with Pushover and additional fields', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create([
        'user_id' => $user->id,
        'type' => 'pushover',
        'value' => 'oldApiToken',
        'additional_field_one' => 'oldUserKey',
        'receive_successful_backup_notifications' => null,
        'receive_failed_backup_notifications' => now(),
    ]);

    $newData = [
        'label' => 'Updated Pushover Notification',
        'type' => 'pushover',
        'value' => 'newApiToken',
        'additional_field_one' => 'newUserKey',
        'success_notification' => true,
        'failed_notification' => false,
    ];

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.label', $newData['label'])
        ->set('form.type', $newData['type'])
        ->set('form.value', $newData['value'])
        ->set('form.additional_field_one', $newData['additional_field_one'])
        ->set('form.success_notification', $newData['success_notification'])
        ->set('form.failed_notification', $newData['failed_notification'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $updatedStream = $notificationStream->fresh();
    expect($updatedStream)->toMatchArray([
        'id' => $notificationStream->id,
        'user_id' => $user->id,
        'label' => $newData['label'],
        'type' => $newData['type'],
        'value' => $newData['value'],
        'additional_field_one' => $newData['additional_field_one'],
    ])
        ->and($updatedStream->receive_successful_backup_notifications)->not->toBeNull()
        ->and($updatedStream->receive_failed_backup_notifications)->toBeNull();
});

it('validates Pushover additional fields during update', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create([
        'user_id' => $user->id,
        'type' => 'pushover',
        'value' => 'validApiToken',
        'additional_field_one' => 'validUserKey',
    ]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('form.additional_field_one', '') // Empty User Key
        ->call('submit')
        ->assertHasErrors(['form.additional_field_one']);
});

it('writes error to log on event', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Log::shouldReceive('error')->once()->with('Error from js script for Telegram authentication.', ['error' => 'Some js error']);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->dispatch('jsError', 'Some js error');
});
