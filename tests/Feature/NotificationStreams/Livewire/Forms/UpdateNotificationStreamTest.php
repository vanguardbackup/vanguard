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
        ->set('form.label', $newData['label'])
        ->set('form.type', $newData['type'])
        ->set('form.value', $newData['value'])
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
        ->set('form.label', $newData['label'])
        ->set('form.type', $newData['type'])
        ->set('form.value', $newData['value'])
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
        ->set('form.label', $newData['label'])
        ->set('form.type', $newData['type'])
        ->set('form.value', $newData['value'])
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
        ->set('form.label', $newData['label'])
        ->set('form.type', $newData['type'])
        ->set('form.value', $newData['value'])
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
