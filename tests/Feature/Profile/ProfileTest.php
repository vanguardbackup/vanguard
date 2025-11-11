<?php

declare(strict_types=1);

use App\Mail\User\DeletionConfirmationMail;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\RemoteServer;
use App\Models\User;
use Livewire\Volt\Volt;

test('profile page is displayed', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/profile');

    $response
        ->assertOk()
        ->assertSeeVolt('profile.update-profile-information-form')
        ->assertSeeVolt('profile.update-password-form');
});

test('delete page is displayed', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/profile/remove');

    $response
        ->assertOk()
        ->assertSeeVolt('profile.delete-user-form');
});

test('profile information can be updated', function (): void {
    Toaster::fake();

    Config::set('app.available_languages', [
        'en' => 'English',
        'ar' => 'Arabic',
    ]);

    $user = User::factory()->create([
        'weekly_summary_opt_in_at' => null,
    ]);

    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('gravatar_email', 'gravatar@example.com')
        ->set('timezone', 'America/New_York')
        ->set('preferred_backup_destination_id', $backupDestination->id)
        ->set('language', 'ar')
        ->set('receiving_weekly_summary_email', true)
        ->set('pagination_count', 50)
        ->call('updateProfileInformation');

    $testable
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertSame('gravatar@example.com', $user->gravatar_email);
    $this->assertSame('America/New_York', $user->timezone);
    $this->assertSame($backupDestination->id, $user->preferred_backup_destination_id);
    $this->assertSame('ar', $user->language);
    $this->assertNull($user->email_verified_at);
    $this->assertNotNull($user->weekly_summary_opt_in_at);
    $this->assertEquals($user->pagination_count, 50);

    Toaster::assertDispatched((__('Profile details saved.')));
});

test('email verification status is unchanged when the email address is unchanged', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->set('pagination_count', 50)
        ->call('updateProfileInformation');

    $testable
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $this->assertNotNull($user->refresh()->getAttribute('email_verified_at'));
});

test('user can delete their account', function (): void {
    Mail::fake();
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.delete-user-form')
        ->set('currentView', 'final-confirmation')
        ->set('password', 'password')
        ->call('deleteUser');

    $testable
        ->assertHasNoErrors()
        ->assertRedirect('/');

    Mail::assertQueued(DeletionConfirmationMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function (): void {
    Mail::fake();
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.delete-user-form')
        ->set('currentView', 'final-confirmation')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $testable
        ->assertHasErrors('password')
        ->assertNoRedirect();

    Mail::assertNotQueued(DeletionConfirmationMail::class);

    $this->assertNotNull($user->fresh());
});

test('user cannot proceed to eligibility check if they have no password', function (): void {
    $user = User::factory()->create(['password' => null]);

    $this->actingAs($user);

    $testable = Volt::test('profile.delete-user-form');

    $this->assertFalse($testable->get('hasPassword'));
});

test('user can proceed to eligibility check if they have a password', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.delete-user-form')
        ->call('proceedToEligibilityCheck');

    $this->assertTrue($testable->get('hasPassword'));
    $this->assertEquals('eligibility', $testable->get('currentView'));
});

test('user can proceed to final confirmation if eligible', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.delete-user-form')
        ->set('isEligible', true)
        ->call('proceedToFinalConfirmation');

    $this->assertEquals('final-confirmation', $testable->get('currentView'));
});

test('user cannot proceed to final confirmation if not eligible', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.delete-user-form')
        ->set('isEligible', false)
        ->call('proceedToFinalConfirmation');

    $this->assertNotEquals('final-confirmation', $testable->get('currentView'));
});

test('account summary is generated correctly', function (): void {
    $user = User::factory()->create();

    BackupTask::factory()->create(['user_id' => $user->id]);
    RemoteServer::factory()->create(['user_id' => $user->id]);
    BackupDestination::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $testable = Volt::test('profile.delete-user-form');

    $accountSummary = $testable->get('accountSummary');

    expect($accountSummary)->toHaveCount(3);

    foreach (['backupTasks', 'remoteServers', 'backupDestinations'] as $key) {
        expect($accountSummary[$key])->toHaveKeys(['count', 'label', 'icon', 'description'])
            ->and($accountSummary[$key]['count'])->toBe(1);
    }

    expect($accountSummary['backupTasks']['label'])->toBe('Backup Tasks')
        ->and($accountSummary['remoteServers']['label'])->toBe('Remote Servers')
        ->and($accountSummary['backupDestinations']['label'])->toBe('Backup Destinations');
});

test('the timezone must be valid', function (): void {

    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('timezone', 'Invalid/Timezone')
        ->set('pagination_count', 50)
        ->call('updateProfileInformation');

    $testable
        ->assertHasErrors('timezone')
        ->assertNoRedirect();
});

test('the preferred backup destination can be nullable - not set', function (): void {

    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('pagination_count', 50)
        ->set('timezone', 'America/New_York')
        ->call('updateProfileInformation');

    $testable
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $user->refresh();

    $this->assertNull($user->preferred_backup_destination_id);
});

test('the preferred backup destination must exist', function (): void {

    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('timezone', 'America/New_York')
        ->set('pagination_count', 50)
        ->set('preferred_backup_destination_id', 999)
        ->call('updateProfileInformation');

    $testable
        ->assertHasErrors('preferred_backup_destination_id')
        ->assertNoRedirect();

    $this->assertNull($user->refresh()->getAttribute('preferred_backup_destination_id'));
});

test('the preferred backup destination must belong to the user', function (): void {

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('timezone', 'America/New_York')
        ->set('pagination_count', 50)
        ->set('preferred_backup_destination_id', $backupDestination->id)
        ->call('updateProfileInformation');

    $testable
        ->assertHasErrors('preferred_backup_destination_id')
        ->assertNoRedirect();

    $this->assertNull($user->refresh()->getAttribute('preferred_backup_destination_id'));
});

test('the language must exist', function (): void {

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    Config::set('app.available_languages', [
        'en' => 'English',
        'ar' => 'Arabic',
    ]);

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('timezone', 'America/New_York')
        ->set('pagination_count', 50)
        ->set('preferred_backup_destination_id', $backupDestination->id)
        ->set('language', 'invalid_language') // Set an invalid language code
        ->call('updateProfileInformation');

    $testable
        ->assertHasErrors(['language' => 'in'])
        ->assertNoRedirect();

    $this->assertEquals('en', $user->language);
});

test('the gravatar email must be an email address', function (): void {

    $user = User::factory()->create([
        'gravatar_email' => 'test@example.com',
    ]);

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('gravatar_email', 'not-an-email')
        ->set('pagination_count', 50)
        ->set('timezone', 'America/New_York')
        ->call('updateProfileInformation');

    $testable
        ->assertHasErrors('gravatar_email')
        ->assertNoRedirect();

    expect($user->gravatar_email)->toBe('test@example.com');
});

test('pagination count can be updated', function (): void {
    $user = User::factory()->create(['pagination_count' => 15]);

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form')
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->set('pagination_count', 30)
        ->call('updateProfileInformation');

    $testable
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $user->refresh();

    expect($user->pagination_count)->toBe(30);
});

test('pagination count must be one of the allowed values', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form')
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->set('pagination_count', 25) // Not in [15, 30, 50, 100]
        ->call('updateProfileInformation');

    $testable
        ->assertHasErrors(['pagination_count' => 'in'])
        ->assertNoRedirect();
});

test('pagination count cannot be less than 1', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form')
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->set('pagination_count', 0)
        ->call('updateProfileInformation');

    $testable
        ->assertHasErrors(['pagination_count' => 'min'])
        ->assertNoRedirect();
});

test('pagination count cannot exceed 100', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form')
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->set('pagination_count', 150)
        ->call('updateProfileInformation');

    $testable
        ->assertHasErrors(['pagination_count' => 'max'])
        ->assertNoRedirect();
});

test('formatted dates are localized correctly', function (): void {
    Config::set('app.available_languages', [
        'en' => 'English',
        'fr' => 'French',
    ]);

    $user = User::factory()->create(['language' => 'fr']);

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form');

    // Assert that the lastMonday and lastFriday are in French
    expect($testable->get('lastMonday'))->toContain('lundi');
    expect($testable->get('lastFriday'))->toContain('vendredi');
});

test('getUserLanguage returns correct language', function (): void {
    Config::set('app.available_languages', [
        'en' => 'English',
        'fr' => 'French',
    ]);

    $user = User::factory()->create(['language' => 'fr']);

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form');

    $reflectionClass = new ReflectionClass($testable->instance());
    $reflectionMethod = $reflectionClass->getMethod('getUserLanguage');

    expect($reflectionMethod->invoke($testable->instance()))->toBe('fr');
});

test('isValidLanguage correctly validates language codes', function (): void {
    Config::set('app.available_languages', [
        'en' => 'English',
        'fr' => 'French',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form');

    $reflectionClass = new ReflectionClass($testable->instance());
    $reflectionMethod = $reflectionClass->getMethod('isValidLanguage');

    expect($reflectionMethod->invoke($testable->instance(), 'en'))->toBeTrue();
    expect($reflectionMethod->invoke($testable->instance(), 'fr'))->toBeTrue();
    expect($reflectionMethod->invoke($testable->instance(), 'de'))->toBeFalse();
});

test('weekly summary email can be opted out', function (): void {
    $user = User::factory()->create([
        'weekly_summary_opt_in_at' => now(),
    ]);

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form')
        ->set('receiving_weekly_summary_email', false)
        ->set('pagination_count', 15)
        ->call('updateProfileInformation');

    $testable->assertHasNoErrors();

    $user->refresh();

    expect($user->weekly_summary_opt_in_at)->toBeNull();
});

test('getPaginationOptions returns correct options', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $testable = Volt::test('profile.update-profile-information-form');

    $reflectionClass = new ReflectionClass($testable->instance());
    $reflectionMethod = $reflectionClass->getMethod('getPaginationOptions');

    $options = $reflectionMethod->invoke($testable->instance());

    expect($options)->toHaveCount(4)
        ->and($options->get(15))->toBe('15 per page')
        ->and($options->get(30))->toBe('30 per page')
        ->and($options->get(50))->toBe('50 per page')
        ->and($options->get(100))->toBe('100 per page');
});
