<?php

declare(strict_types=1);

use App\Livewire\NotificationStreams\Buttons\RemoveNotificationStream;
use App\Models\NotificationStream;
use App\Models\User;

test('the component can be rendered', function (): void {

    Livewire::test(RemoveNotificationStream::class, ['notificationStream' => NotificationStream::factory()->create()])
        ->assertStatus(200);
});

test('a backup destination can be deleted by its creator', function (): void {

    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(RemoveNotificationStream::class, ['notificationStream' => $notificationStream])
        ->call('delete');

    $this->assertDatabaseMissing('notification_streams', ['id' => $notificationStream->id]);
    $this->assertAuthenticatedAs($user);
});

test('a backup destination cannot be deleted by another user', function (): void {

    $user = User::factory()->create();
    $notificationStream = NotificationStream::factory()->create();

    $this->actingAs($user);

    Livewire::test(RemoveNotificationStream::class, ['notificationStream' => NotificationStream::factory()->create()])
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('notification_streams', ['id' => $notificationStream->id]);
    $this->assertAuthenticatedAs($user);
});
