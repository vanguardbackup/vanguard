<?php

declare(strict_types=1);

use App\Models\BackupDestination;
use App\Models\User;

test('the page can be rendered by by the owner of the backup destination', function () {

    $user = User::factory()->create();

    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('backup-destinations.edit', $backupDestination));

    $response->assertOk();
    $response->assertViewIs('backup-destinations.edit');
    $response->assertViewHas('backupDestination', $backupDestination);

    $this->assertAuthenticatedAs($user);
    $this->assertEquals($user->id, $backupDestination->user_id);
});

test('the page is not rendered by unauthorized users', function () {

    $user = User::factory()->create();

    $backupDestination = BackupDestination::factory()->create();

    $response = $this->actingAs($user)->get(route('backup-destinations.edit', $backupDestination));

    $response->assertForbidden();

    $this->assertAuthenticatedAs($user);

    $this->assertNotEquals($user->id, $backupDestination->user_id);
});

test('the page is not rendered by guests', function () {

    $backupDestination = BackupDestination::factory()->create();

    $response = $this->get(route('backup-destinations.edit', $backupDestination));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});
