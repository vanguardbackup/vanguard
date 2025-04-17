<?php

use App\Livewire\Admin\User\UnsuspendUserModal;
use App\Models\User;
use App\Models\UserSuspension;

test('A suspended user can be unsuspended', function () {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $factory = UserSuspension::factory()->permanent()->create();
    $suspendedUser = $factory->user;

    Livewire::actingAs($adminUser)
        ->test(UnsuspendUserModal::class, ['user' => $suspendedUser])
        ->set('unsuspensionNote', 'Hello world!')
        ->call('unsuspendUser')
        ->assertDispatched('close-modal');

    $this->assertDatabaseHas('user_suspensions', [
        'unsuspension_note' => 'Hello world!',
        'user_id' => $suspendedUser->id,
        'lifted_by_admin_user_id' => $adminUser->id,
        'lifted_at' => now(),
    ]);
});

test('A user that is not suspended cannot be unsuspended', function () {
    Toaster::fake();
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $user = User::factory()->create();

    Livewire::actingAs($adminUser)
        ->test(UnsuspendUserModal::class, ['user' => $user])
        ->call('unsuspendUser')
        ->assertDispatched('close-modal');

    Toaster::assertDispatched('Unable to unsuspend user - not currently suspended.');
});
