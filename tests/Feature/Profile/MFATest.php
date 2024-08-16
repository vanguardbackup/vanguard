<?php

declare(strict_types=1);

use App\Mail\User\TwoFactor\DisabledMail;
use App\Mail\User\TwoFactor\EnabledMail;
use App\Mail\User\TwoFactor\RegeneratedBackupCodesMail;
use App\Mail\User\TwoFactor\ViewedBackupCodesMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Volt;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('the component can be rendered', function (): void {
    Volt::test('profile.multi-factor-authentication-manager')
        ->assertOk();
});

test('the page can be visited by authenticated users', function (): void {
    $this->get(route('profile.mfa'))
        ->assertOk()
        ->assertSeeLivewire('profile.multi-factor-authentication-manager');
});

test('the page cannot be visited by guests', function (): void {
    Auth::logout();
    $this->get(route('profile.mfa'))
        ->assertRedirect('login');
    $this->assertGuest();
});

test('user can start 2fa setup process', function (): void {
    $testable = Volt::test('profile.multi-factor-authentication-manager');

    $testable->call('startSetup2FA')
        ->assertSet('currentView', 'setup-app')
        ->assertSet('qrCodeSvg', fn ($value): bool => ! empty($value))
        ->assertSet('twoFactorSecret', fn ($value): bool => ! empty($value));

    expect($this->user->fresh()->twoFactorAuth)->not->toBeNull()
        ->and($this->user->fresh()->twoFactorAuth->shared_secret)->not->toBeNull();
});

test('user can enable 2fa with valid code', function (): void {
    Mail::fake();
    $testable = Volt::test('profile.multi-factor-authentication-manager');

    $testable->call('startSetup2FA');
    $secret = $testable->get('twoFactorSecret');

    $validCode = $this->user->makeTwoFactorCode();

    $testable->set('verificationCode', $validCode)
        ->call('verifyAndEnable2FA')
        ->assertSet('currentView', 'success')
        ->assertSet('currentMethod', 'app')
        ->assertSet('showingRecoveryCodes', true);

    expect($this->user->fresh()->hasTwoFactorEnabled())->toBeTrue();
    Mail::assertQueued(EnabledMail::class);
});

test('user cannot enable 2fa with invalid code', function (): void {
    Mail::fake();
    $testable = Volt::test('profile.multi-factor-authentication-manager');

    $testable->call('startSetup2FA')
        ->set('verificationCode', '000000')
        ->call('verifyAndEnable2FA')
        ->assertHasErrors(['verificationCode'])
        ->assertSet('currentView', 'setup-app');

    expect($this->user->fresh()->hasTwoFactorEnabled())->toBeFalse();
    Mail::assertNotQueued(EnabledMail::class);
});

test('user can view backup codes', function (): void {
    Mail::fake();
    $this->user->createTwoFactorAuth();
    $this->user->enableTwoFactorAuth();

    $testable = Volt::test('profile.multi-factor-authentication-manager');

    $testable->call('viewBackupCodes')
        ->assertDispatched('open-modal', 'confirm-password')
        ->assertSet('confirmationAction', 'viewBackupCodes');

    $testable->set('password', 'password')
        ->call('confirmPassword')
        ->assertSet('currentView', 'backup-codes')
        ->assertSet('backupCodes', fn ($codes): bool => count($codes) > 0);

    Mail::assertQueued(ViewedBackupCodesMail::class);
});

test('user can regenerate backup codes', function (): void {
    Mail::fake();
    $this->user->createTwoFactorAuth();
    $this->user->enableTwoFactorAuth();

    $testable = Volt::test('profile.multi-factor-authentication-manager');

    $testable->call('viewBackupCodes');
    $originalCodes = $testable->get('backupCodes');

    $testable->call('performRegenerateBackupCodes')
        ->assertSet('backupCodes', fn ($newCodes): bool => $newCodes != $originalCodes);
    Mail::assertQueued(RegeneratedBackupCodesMail::class);
});

test('user can disable 2fa', function (): void {
    Mail::fake();
    $this->user->createTwoFactorAuth();
    $this->user->enableTwoFactorAuth();

    Volt::test('profile.multi-factor-authentication-manager')
        ->set('password', 'password')
        ->call('disable2FA')
        ->assertSet('currentMethod', 'none')
        ->assertSet('showingRecoveryCodes', false)
        ->assertDispatched('close-modal');

    expect($this->user->fresh()->hasTwoFactorEnabled())->toBeFalse();
    Mail::assertQueued(DisabledMail::class);
});

test('user cannot disable 2fa with incorrect password', function (): void {
    Mail::fake();
    $this->user->createTwoFactorAuth();
    $this->user->enableTwoFactorAuth();

    Volt::test('profile.multi-factor-authentication-manager')
        ->set('password', 'wrong-password')
        ->call('disable2FA')
        ->assertHasErrors(['password'])
        ->assertSet('currentMethod', 'app');

    expect($this->user->fresh()->hasTwoFactorEnabled())->toBeTrue();
    Mail::assertNotQueued(DisabledMail::class);
});

test('user can download backup codes', function (): void {
    $this->user->createTwoFactorAuth();
    $this->user->enableTwoFactorAuth();

    $testable = Volt::test('profile.multi-factor-authentication-manager');

    $testable->call('downloadBackupCodes');

    $testable->assertDispatched('download');
});
