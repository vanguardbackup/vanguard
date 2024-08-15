<?php

declare(strict_types=1);

use App\Http\Middleware\EnforceTwoFactor;
use App\Mail\User\TwoFactor\BackupCodeConsumedMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
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

        $this->assertTrue($this->user->fresh()->hasTwoFactorEnabled());
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

        $this->assertFalse($this->user->fresh()->hasTwoFactorEnabled());
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

        $this->assertAuthenticated();
        $this->assertFalse($this->user->hasTwoFactorEnabled());
    });

    test('A user with two-factor enabled does get asked to validate', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        Route::get('/test-login', function (): string {
            return 'Login Successful';
        })->middleware(['auth', EnforceTwoFactor::class])->name('test.login');

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $this->user->email)
            ->set('form.password', 'password');

        $this->assertTrue($this->user->fresh()->hasTwoFactorEnabled());

        $component->call('login');

        $this->assertAuthenticated();

        $response = $this->get(route('test.login'));

        $response->assertRedirect(route('two-factor.challenge'));

        $this->assertTrue($this->user->fresh()->hasTwoFactorEnabled());
    });
});

describe('Two-Factor Code Validation', function (): void {
    test('A user with two-factor enabled can successfully validate their code', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        $this->actingAs($this->user);

        $validCode = $this->user->makeTwoFactorCode();

        $response = $this->post(route('two-factor.challenge'), [
            'code' => $validCode,
        ]);

        $response->assertRedirect(route('overview'));
        $this->assertNotNull($this->user->fresh()->two_factor_verified_token);
    });

    test('A user with two-factor enabled cannot validate with an invalid code', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        $this->actingAs($this->user);

        $response = $this->post(route('two-factor.challenge'), [
            'code' => '000000', // Invalid code
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('code');
    });

    test('Invalid two-factor code format returns validation error', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        $this->actingAs($this->user);

        $response = $this->post(route('two-factor.challenge'), ['code' => '12345']); // Invalid format

        $response->assertRedirect();
        $response->assertSessionHasErrors('code');
    });
});

describe('Recovery Codes', function (): void {
    test('Generating recovery codes fires TwoFactorRecoveryCodesGenerated event', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        Event::fake();

        $this->user->generateRecoveryCodes();

        Event::assertDispatched(TwoFactorRecoveryCodesGenerated::class);
        $this->assertNotNull($this->user->twoFactorAuth->recovery_codes);
    });

    test('User can use recovery code for two-factor authentication', function (): void {
        Mail::fake();
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        $recoveryCodes = $this->user->getRecoveryCodes();
        $recoveryCode = $recoveryCodes->first()['code'];

        $this->actingAs($this->user);

        $response = $this->post(route('two-factor.challenge'), ['code' => $recoveryCode]);

        $response->assertRedirect(route('overview'));

        $this->user->refresh();
        $updatedRecoveryCodes = $this->user->getRecoveryCodes();
        $usedCode = $updatedRecoveryCodes->firstWhere('code', $recoveryCode);
        $this->assertNotNull($usedCode['used_at']);

        Mail::assertQueued(BackupCodeConsumedMail::class);
    });

    test('User can generate new recovery codes', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        $originalCodes = $this->user->getRecoveryCodes();

        $newCodes = $this->user->generateRecoveryCodes();

        $this->assertCount(10, $newCodes);  // Assuming default of 10 codes
        $this->assertNotEquals($originalCodes, $newCodes);
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
        $this->assertAuthenticated();
        $this->assertFalse($this->user->hasTwoFactorEnabled());
    });

    test('Two-factor challenge view is rendered for GET requests', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        $this->actingAs($this->user);

        $response = $this->get(route('two-factor.challenge'));

        $response->assertViewIs('auth.two-factor-challenge');
    });
});

describe('EnforceTwoFactor Middleware', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->middleware = new EnforceTwoFactor;
        Route::get('two-factor/challenge', fn (): string => 'challenge')->name('two-factor.challenge');
    });

    test('User with two-factor enabled and no valid cookie is redirected', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        $request = Request::create('/test');
        $request->setUserResolver(fn () => $this->user);

        $response = $this->middleware->handle($request, function (): Response {
            return new Response('OK');
        });

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('two-factor.challenge'));
    });

    test('JSON request for user with two-factor enabled and no valid cookie returns 403', function (): void {
        $this->user->createTwoFactorAuth();
        $this->user->enableTwoFactorAuth();

        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->middleware->handle($request, function (): Response {
            return new Response('OK');
        });

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

        $response = $this->middleware->handle($request, function (): Response {
            return new Response('OK');
        });

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

        $response = $this->middleware->handle($request, function (): Response {
            return new Response('OK');
        });

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

        $response = $this->middleware->handle($request, function (): Response {
            return new Response('OK');
        });

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

        $response = $this->middleware->handle($request, function (): Response {
            return new Response('OK');
        });

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('two-factor.challenge'));
    });
});
