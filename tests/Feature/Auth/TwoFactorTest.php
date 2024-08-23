<?php

declare(strict_types=1);

use App\Http\Middleware\EnforceTwoFactor;
use App\Mail\User\TwoFactor\BackupCodeConsumedMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Laragear\TwoFactor\Events\TwoFactorDisabled;
use Laragear\TwoFactor\Events\TwoFactorEnabled;
use Laragear\TwoFactor\Events\TwoFactorRecoveryCodesGenerated;
use Livewire\Volt\Volt;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

describe('Two-Factor Authentication Setup', function (): void {
    test('User can create and enable two-factor authentication', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        expect($this->user->fresh()->hasTwoFactorEnabled())->toBeTrue();
    });

    test('Enabling two-factor authentication fires TwoFactorEnabled event', function (): void {
        Event::fake();

        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        Event::assertDispatched(TwoFactorEnabled::class);
    });

    test('User can disable two-factor authentication', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();
        $this->user->disableTwoFactorAuth();

        expect($this->user->fresh()->hasTwoFactorEnabled())->toBeFalse();
    });

    test('Disabling two-factor authentication fires TwoFactorDisabled event', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        Event::fake();

        $this->user->disableTwoFactorAuth();

        Event::assertDispatched(TwoFactorDisabled::class);
    });
});

describe('Two-Factor Authentication Login Flow', function (): void {
    test('A user without two-factor enabled does not get asked to validate', function (): void {
        $component = Volt::test('pages.auth.login')
            ->set('form.email', $this->user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('overview', absolute: false));

        expect($this->isAuthenticated())->toBeTrue()
            ->and($this->user->hasTwoFactorEnabled())->toBeFalse();
    });

    test('A user with two-factor enabled does get asked to validate', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        Route::get('/test-login', fn (): string => 'Login Successful')
            ->middleware(['auth', EnforceTwoFactor::class])
            ->name('test.login');

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $this->user->email)
            ->set('form.password', 'password');

        expect($this->user->fresh()->hasTwoFactorEnabled())->toBeTrue();

        $component->call('login');

        expect($this->isAuthenticated())->toBeTrue();

        $response = $this->get(route('test.login'));

        $response->assertRedirect(route('two-factor.challenge'));

        expect($this->user->fresh()->hasTwoFactorEnabled())->toBeTrue();
    });
});

describe('Two-Factor Challenge', function (): void {
    beforeEach(function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();
        Auth::login($this->user);
    });

    test('two-factor challenge page can be rendered', function (): void {
        $response = $this->get(route('two-factor.challenge'));

        $response->assertStatus(200)
            ->assertSeeVolt('pages.auth.two-factor-challenge');
    });

    test('user cannot submit invalid two-factor authentication code', function (): void {
        $invalidCode = '000000';

        $component = Volt::test('pages.auth.two-factor-challenge')
            ->set('code', $invalidCode)
            ->call('submit');

        expect($component->error)->not->toBeNull();

        $this->user->refresh();
        expect($this->user->two_factor_verified_token)->toBeNull();
    });

    test('user can toggle between auth code and recovery code input', function (): void {
        $testable = Volt::test('pages.auth.two-factor-challenge');

        expect($testable->isRecoveryCode)->toBeFalse();

        $testable->call('toggleCodeType');

        expect($testable->isRecoveryCode)->toBeTrue();
    });

    test('user can submit valid recovery code', function (): void {
        Mail::fake();
        $recoveryCodes = $this->user->generateRecoveryCodes();
        $validRecoveryCode = (string) $recoveryCodes[0]['code'];

        $component = Volt::test('pages.auth.two-factor-challenge')
            ->set('isRecoveryCode', true)
            ->set('code', $validRecoveryCode)
            ->call('submit');

        expect($component->error)->toBeNull();

        $this->user->refresh();
        expect($this->user->two_factor_verified_token)->not->toBeNull();
        Mail::assertQueued(BackupCodeConsumedMail::class);
    });

    test('user cannot submit invalid recovery code', function (): void {
        $invalidRecoveryCode = 'invalid-recovery-code';

        $component = Volt::test('pages.auth.two-factor-challenge')
            ->set('isRecoveryCode', true)
            ->set('code', $invalidRecoveryCode)
            ->call('submit');

        expect($component->error)->not->toBeNull();

        $this->user->refresh();
        expect($this->user->two_factor_verified_token)->toBeNull();
    });

    test('rate limiting is applied on too many failed attempts', function (): void {
        for ($i = 0; $i < 5; $i++) {
            Volt::test('pages.auth.two-factor-challenge')
                ->set('code', '000000')
                ->call('submit');
        }

        $component = Volt::test('pages.auth.two-factor-challenge')
            ->set('code', '000000')
            ->call('submit');

        expect($component->error)->toContain('Too many attempts');
    });

    test('auth code input automatically submits when 6 digits are entered', function (): void {
        $validCode = $this->user->makeTwoFactorCode();

        $testable = Volt::test('pages.auth.two-factor-challenge');

        foreach (str_split($validCode) as $digit) {
            $testable->set('code', $testable->get('code') . $digit);
        }

        expect($testable->error)->toBeNull();

        $this->user->refresh();
        expect($this->user->two_factor_verified_token)->not->toBeNull();
    });

    test('error is cleared when toggling between auth code and recovery code', function (): void {
        $component = Volt::test('pages.auth.two-factor-challenge')
            ->set('code', '000000')
            ->call('submit');

        expect($component->error)->not->toBeNull();

        $component->call('toggleCodeType');

        expect($component->error)->toBeNull()
            ->and($component->code)->toBe('');
    });
});

describe('Recovery Codes', function (): void {
    test('Generating recovery codes fires TwoFactorRecoveryCodesGenerated event', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        Event::fake();

        $this->user->generateRecoveryCodes();

        Event::assertDispatched(TwoFactorRecoveryCodesGenerated::class);
        expect($this->user->twoFactorAuth->recovery_codes)->not->toBeNull();
    });

    test('User can generate new recovery codes', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        $originalCodes = $this->user->getRecoveryCodes();

        $newCodes = $this->user->generateRecoveryCodes();

        expect($newCodes)->toHaveCount(10)
            ->and($newCodes)->not->toEqual($originalCodes);
    });
});

describe('Two-Factor Authentication Middleware', function (): void {
    test('A guest cannot access the two factor route', function (): void {
        $response = $this->get(route('two-factor.challenge'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    });

    test('A user without two factor auth gets redirected when they visit the two-factor challenge route', function (): void {
        $response = $this->actingAs($this->user)->get(route('two-factor.challenge'));

        $response->assertRedirect(route('overview'));
        expect($this->isAuthenticated())->toBeTrue()
            ->and($this->user->hasTwoFactorEnabled())->toBeFalse();
    });
});

describe('EnforceTwoFactor Middleware', function (): void {
    beforeEach(function (): void {
        $this->middleware = new EnforceTwoFactor;
        Route::get('two-factor/challenge', fn (): string => 'challenge')->name('two-factor.challenge');
    });

    test('User with two-factor enabled and no valid cookie is redirected', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        $request = Request::create('/test');
        $request->setUserResolver(fn () => $this->user);

        $response = $this->middleware->handle($request, fn (): Response => new Response('OK'));

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('two-factor.challenge'));
    });

    test('JSON request for user with two-factor enabled and no valid cookie returns 403', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->middleware->handle($request, fn (): Response => new Response('OK'));

        expect($response)->toBeInstanceOf(JsonResponse::class)
            ->and($response->getStatusCode())->toBe(403)
            ->and($response->getData(true))->toHaveKey('message', 'Two-factor authentication required.');
    });

    test('User with expired two-factor cookie is redirected', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();
        $this->user->two_factor_verified_token = Hash::make('valid_token');
        $this->user->save();

        $request = Request::create('/test');
        $request->setUserResolver(fn () => $this->user);
        $request->cookies->set('two_factor_verified', encrypt('invalid_token'));

        $response = $this->middleware->handle($request, fn (): Response => new Response('OK'));

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('two-factor.challenge'));
    });

    test('User with significant IP change is redirected', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();
        $this->user->last_two_factor_ip = '192.168.1.1';
        $this->user->last_two_factor_at = now();
        $this->user->two_factor_verified_token = Hash::make('valid_token');
        $this->user->save();

        $request = Request::create('/test');
        $request->setUserResolver(fn () => $this->user);
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $request->cookies->set('two_factor_verified', encrypt('valid_token'));

        $response = $this->middleware->handle($request, fn (): Response => new Response('OK'));

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('two-factor.challenge'));
    });

    test('User with last two-factor authentication over 30 days ago is redirected', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();
        $this->user->last_two_factor_at = now()->subDays(31);
        $this->user->last_two_factor_ip = '192.168.1.1';
        $this->user->two_factor_verified_token = Hash::make('valid_token');
        $this->user->save();

        $request = Request::create('/test');
        $request->setUserResolver(fn () => $this->user);
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $request->cookies->set('two_factor_verified', encrypt('valid_token'));

        $response = $this->middleware->handle($request, fn (): Response => new Response('OK'));

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('two-factor.challenge'));
    });

    test('User with null last_two_factor_ip is redirected', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();
        $this->user->last_two_factor_ip = null;
        $this->user->last_two_factor_at = now();
        $this->user->two_factor_verified_token = Hash::make('valid_token');
        $this->user->save();

        $request = Request::create('/test');
        $request->setUserResolver(fn () => $this->user);
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $request->cookies->set('two_factor_verified', encrypt('valid_token'));

        $response = $this->middleware->handle($request, fn (): Response => new Response('OK'));

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('two-factor.challenge'));
    });
});
