<?php

declare(strict_types=1);

use App\Livewire\NotificationStreams\Forms\UpdateNotificationStream;
use App\Models\NotificationStream;
use App\Models\User;
use Livewire\Livewire;

it('renders successfully', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->assertStatus(200);
});

it('updates successfully with email', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'value' => 'old-email@example.com',
    ]);

    $newData = [
        'label' => 'Updated Email Notification',
        'type' => 'email',
        'value' => 'new-email@example.com',
    ];

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('label', $newData['label'])
        ->set('type', $newData['type'])
        ->set('value', $newData['value'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $this->assertDatabaseHas('notification_streams', [
        'id' => $notificationStream->id,
        'user_id' => $user->id,
        'label' => $newData['label'],
        'type' => $newData['type'],
        'value' => $newData['value'],
    ]);
});

it('updates successfully with Discord webhook', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create([
        'user_id' => $user->id,
        'type' => 'discord_webhook',
        'value' => 'https://discord.com/api/webhooks/old',
    ]);

    $newData = [
        'label' => 'Updated Discord Webhook',
        'type' => 'discord_webhook',
        'value' => 'https://discord.com/api/webhooks/123456789/abcdefghijklmnop',
    ];

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('label', $newData['label'])
        ->set('type', $newData['type'])
        ->set('value', $newData['value'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $this->assertDatabaseHas('notification_streams', [
        'id' => $notificationStream->id,
        'user_id' => $user->id,
        'label' => $newData['label'],
        'type' => $newData['type'],
        'value' => $newData['value'],
    ]);
});

it('updates successfully with Slack webhook', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create([
        'user_id' => $user->id,
        'type' => 'slack_webhook',
        'value' => 'https://hooks.slack.com/services/old',
    ]);

    $newData = [
        'label' => 'Updated Slack Webhook',
        'type' => 'slack_webhook',
        'value' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
    ];

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('label', $newData['label'])
        ->set('type', $newData['type'])
        ->set('value', $newData['value'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $this->assertDatabaseHas('notification_streams', [
        'id' => $notificationStream->id,
        'user_id' => $user->id,
        'label' => $newData['label'],
        'type' => $newData['type'],
        'value' => $newData['value'],
    ]);
});

it('validates required fields', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('label', '')
        ->set('type', '')
        ->set('value', '')
        ->call('submit')
        ->assertHasErrors(['label', 'type', 'value']);
});

it('validates label max length', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('label', str_repeat('a', 256))
        ->call('submit')
        ->assertHasErrors(['label' => 'max']);
});

it('validates email format', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('type', 'email')
        ->set('value', 'invalid-email')
        ->call('submit')
        ->assertHasErrors(['value']);
});

it('validates Discord webhook format', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('type', 'discord_webhook')
        ->set('value', 'https://invalid-discord-webhook.com')
        ->call('submit')
        ->assertHasErrors(['value']);
});

it('validates Slack webhook format', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('type', 'slack_webhook')
        ->set('value', 'https://invalid-slack-webhook.com')
        ->call('submit')
        ->assertHasErrors(['value']);
});

it('updates validation message when type changes', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    $component = Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('label', 'Test Notification')
        ->set('value', '');

    $component->set('type', 'email')
        ->call('submit')
        ->assertHasErrors(['value' => 'Please enter an email address.']);

    $component->set('type', 'discord_webhook')
        ->call('submit')
        ->assertHasErrors(['value' => 'Please enter a Discord webhook URL.']);

    $component->set('type', 'slack_webhook')
        ->call('submit')
        ->assertHasErrors(['value' => 'Please enter a Slack webhook URL.']);
});

it('allows authorized users to update', function (): void {
    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $user->id]);

    $newData = [
        'label' => 'Authorized Update',
        'type' => 'email',
        'value' => 'authorized@example.com',
    ];

    Livewire::actingAs($user)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->set('label', $newData['label'])
        ->set('type', $newData['type'])
        ->set('value', $newData['value'])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('notification-streams.index'));

    $this->assertDatabaseHas('notification_streams', [
        'id' => $notificationStream->id,
        'user_id' => $user->id,
        'label' => $newData['label'],
        'type' => $newData['type'],
        'value' => $newData['value'],
    ]);
});

it('prevents unauthorized users from updating', function (): void {
    $owner = User::factory()->create();
    $unauthorizedUser = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create(['user_id' => $owner->id]);

    Livewire::actingAs($unauthorizedUser)
        ->test(UpdateNotificationStream::class, ['notificationStream' => $notificationStream])
        ->assertStatus(403);
});
