<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laragear\TwoFactor\Facades\Auth2FA;
use Livewire\Form;

/**
 * Handles the login form logic, including two-factor authentication.
 */
class LoginForm extends Form
{
    /** @var string The user's email address. */
    public string $email = '';

    /** @var string The user's password. */
    public string $password = '';

    /** @var bool Whether to remember the user's login. */
    public bool $remember = false;

    /** @var string|null The two-factor authentication code. */
    public ?string $two_factor_code = null;

    /** @var bool Whether two-factor authentication is required. */
    public bool $requires_2fa = false;

    /**
     * Define the validation rules for the login form.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'two_factor_code' => ['nullable', 'string'],
        ];
    }

    /**
     * Define the custom error messages for the login form.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('Please enter your email address.'),
            'email.email' => __('Please enter a valid email address.'),
            'password.required' => __('Please enter your password.'),
            'two_factor_code.required' => __('Please enter your two-factor authentication code.'),
        ];
    }

    /**
     * Attempt to authenticate the user.
     *
     * @throws ValidationException
     */
    public function authenticate(): bool
    {
        Log::info('LoginForm: Starting authentication attempt', ['email' => $this->email]);

        try {
            $credentials = [
                'email' => $this->email,
                'password' => $this->password,
            ];

            if (Auth2FA::attempt($credentials, $this->remember)) {
                Log::info('LoginForm: Authentication successful', ['email' => $this->email]);

                return true;
            }

            Log::warning('LoginForm: Authentication failed', ['email' => $this->email]);
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);

        } catch (HttpResponseException $e) {
            Log::info('LoginForm: 2FA required', ['email' => $this->email]);
            $this->requires_2fa = true;
            throw $e;
        } catch (Exception $e) {
            Log::error('LoginForm: Exception during authentication', [
                'email' => $this->email,
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw ValidationException::withMessages([
                'email' => __('An error occurred during login. Please try again.'),
            ]);
        }
    }

    /**
     * Confirm the two-factor authentication code.
     *
     * @throws ValidationException
     */
    public function confirmTwoFactor(): bool
    {
        if (! $this->two_factor_code) {
            throw ValidationException::withMessages([
                'two_factor_code' => __('Please enter your two-factor authentication code.'),
            ]);
        }

        try {
            if (Auth2FA::input($this->two_factor_code)) {
                Log::info('LoginForm: 2FA validation successful', ['email' => $this->email]);

                return true;
            }

            Log::warning('LoginForm: 2FA validation failed', ['email' => $this->email]);
            throw ValidationException::withMessages([
                'two_factor_code' => __('The provided two-factor code is invalid.'),
            ]);

        } catch (Exception $e) {
            Log::error('LoginForm: Exception during 2FA validation', [
                'email' => $this->email,
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw ValidationException::withMessages([
                'two_factor_code' => __('An error occurred during 2FA validation. Please try again.'),
            ]);
        }
    }
}
