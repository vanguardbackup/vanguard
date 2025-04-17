<?php

declare(strict_types=1);

use App\Livewire\Admin\User\SuspendUserModal;
use App\Models\User;
use App\Models\UserSuspension;
use Carbon\Carbon;

test('A user can be suspended temporarily', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $userToSuspend = User::factory()->create();

    Livewire::actingAs($adminUser)
        ->test(SuspendUserModal::class, ['user' => $userToSuspend])
        ->set('suspensionReason', 'Harassment')
        ->set('suspendUntil', Carbon::now()->addDay()->format('Y-m-d'))
        ->set('privateNote', 'Blah blah!')
        ->set('notifyUserWhenSuspensionHasBeenLifted', true)
        ->call('suspendUser');

    $suspension = UserSuspension::where('user_id', $userToSuspend->id)->first();

    $this->assertNotNull($suspension);
    $this->assertEquals($userToSuspend->id, $suspension->user_id);
    $this->assertEquals($adminUser->id, $suspension->admin_user_id);
    $this->assertEquals('Harassment', $suspension->suspended_reason);
    $this->assertEquals('Blah blah!', $suspension->private_note);
});

test('A user can be suspended permanently', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $userToSuspend = User::factory()->create();

    Livewire::actingAs($adminUser)
        ->test(SuspendUserModal::class, ['user' => $userToSuspend])
        ->set('suspensionReason', 'Harassment')
        ->set('permanentlySuspend', true)
        ->set('privateNote', 'Blah blah!')
        ->call('suspendUser');

    $this->assertDatabaseHas('user_suspensions', [
        'user_id' => $userToSuspend->id,
        'admin_user_id' => $adminUser->id,
        'suspended_at' => now(),
        'suspended_reason' => 'Harassment',
        'private_note' => 'Blah blah!',
        'suspended_until' => null, // Null is permanent!
    ]);
});

test('An admin cannot be suspended', function (): void {
    Toaster::fake();
    Config::set('auth.admin_email_addresses', ['admin@email.com', 'admin2@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);
    $userToSuspend = User::factory()->create(['email' => 'admin2@email.com']);

    Livewire::actingAs($adminUser)
        ->test(SuspendUserModal::class, ['user' => $userToSuspend])
        ->set('suspensionReason', 'Harassment')
        ->set('permanentlySuspend', true)
        ->set('privateNote', 'Blah blah!')
        ->call('suspendUser');

    Toaster::assertDispatched('Unable to suspend user.');

    $this->assertDatabaseMissing('user_suspensions', [
        'user_id' => $userToSuspend->id,
        'admin_user_id' => $adminUser->id,
    ]);
});

test('A user with an active suspension cannot be resuspended', function (): void {
    Toaster::fake();
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);

    $userToSuspend = UserSuspension::factory()->create();
    $user = $userToSuspend->user;

    Livewire::actingAs($adminUser)
        ->test(SuspendUserModal::class, ['user' => $user])
        ->set('suspensionReason', 'Harassment')
        ->set('suspendUntil', Carbon::now()->addDay())
        ->set('privateNote', 'Blah blah!')
        ->call('suspendUser');

    Toaster::assertDispatched('Unable to suspend user.');

    $this->assertDatabaseMissing('user_suspensions', [
        'user_id' => $userToSuspend->id,
        'admin_user_id' => $adminUser->id,
    ]);
});

test('validation fails when no suspension reason is provided', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);
    $userToSuspend = User::factory()->create();

    Livewire::actingAs($adminUser)
        ->test(SuspendUserModal::class, ['user' => $userToSuspend])
        ->set('suspendUntil', Carbon::now()->addDay())
        ->call('suspendUser')
        ->assertHasErrors(['suspensionReason' => 'required']);

    $this->assertDatabaseMissing('user_suspensions', [
        'user_id' => $userToSuspend->id,
    ]);
});

test('validation fails when suspension date is not provided for temporary suspension', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);
    $userToSuspend = User::factory()->create();

    Livewire::actingAs($adminUser)
        ->test(SuspendUserModal::class, ['user' => $userToSuspend])
        ->set('suspensionReason', 'Harassment')
        ->set('suspendUntil', '')
        ->call('suspendUser')
        ->assertHasErrors(['suspendUntil' => 'required']);

    $this->assertDatabaseMissing('user_suspensions', [
        'user_id' => $userToSuspend->id,
    ]);
});

test('validation fails when suspension date is in the past', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);
    $userToSuspend = User::factory()->create();

    Livewire::actingAs($adminUser)
        ->test(SuspendUserModal::class, ['user' => $userToSuspend])
        ->set('suspensionReason', 'Harassment')
        ->set('suspendUntil', Carbon::now()->subDay()->format('Y-m-d'))
        ->call('suspendUser')
        ->assertHasErrors(['suspendUntil' => 'after_or_equal']);

    $this->assertDatabaseMissing('user_suspensions', [
        'user_id' => $userToSuspend->id,
    ]);
});

test('validation fails when suspension reason is invalid', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    Config::set('suspensions.reasons', ['Spam', 'Harassment']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);
    $userToSuspend = User::factory()->create();

    Livewire::actingAs($adminUser)
        ->test(SuspendUserModal::class, ['user' => $userToSuspend])
        ->set('suspensionReason', 'InvalidReason')
        ->set('suspendUntil', Carbon::now()->addDay()->format('Y-m-d'))
        ->call('suspendUser')
        ->assertHasErrors(['suspensionReason' => 'in']);

    $this->assertDatabaseMissing('user_suspensions', [
        'user_id' => $userToSuspend->id,
    ]);
});

test('toaster shows success message after successful suspension', function (): void {
    Toaster::fake();
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);
    $userToSuspend = User::factory()->create();

    Livewire::actingAs($adminUser)
        ->test(SuspendUserModal::class, ['user' => $userToSuspend])
        ->set('suspensionReason', 'Harassment')
        ->set('suspendUntil', Carbon::now()->addDay()->format('Y-m-d'))
        ->call('suspendUser');

    Toaster::assertDispatched('User has been suspended.');
});

test('modal closes after successful suspension', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);
    $userToSuspend = User::factory()->create();

    $component = Livewire::actingAs($adminUser)
        ->test(SuspendUserModal::class, ['user' => $userToSuspend])
        ->set('suspensionReason', 'Harassment')
        ->set('suspendUntil', Carbon::now()->addDay()->format('Y-m-d'))
        ->call('suspendUser');

    $component->assertDispatched('close-modal', 'suspend-user-modal-' . $userToSuspend->id);
});

test('temporary suspension without notification', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);
    $userToSuspend = User::factory()->create();
    $suspendUntil = Carbon::now()->addDay();

    Livewire::actingAs($adminUser)
        ->test(SuspendUserModal::class, ['user' => $userToSuspend])
        ->set('suspensionReason', 'Harassment')
        ->set('suspendUntil', $suspendUntil->format('Y-m-d'))
        ->set('notifyUserWhenSuspensionHasBeenLifted', false)
        ->call('suspendUser');

    $this->assertDatabaseHas('user_suspensions', [
        'user_id' => $userToSuspend->id,
        'notify_user_upon_suspension_being_lifted_at' => null,
    ]);
});

test('updatedPermanentlySuspend sets suspendUntil to tomorrow when toggling from permanent to temporary', function (): void {
    Config::set('auth.admin_email_addresses', ['admin@email.com']);
    $adminUser = User::factory()->create(['email' => 'admin@email.com']);
    $userToSuspend = User::factory()->create();

    $component = Livewire::actingAs($adminUser)
        ->test(SuspendUserModal::class, ['user' => $userToSuspend])
        ->set('permanentlySuspend', true)
        ->set('suspendUntil', '');

    $component->set('permanentlySuspend', false);

    $expectedDate = Carbon::tomorrow()->format('Y-m-d');
    $this->assertEquals($expectedDate, $component->get('suspendUntil'));
});
