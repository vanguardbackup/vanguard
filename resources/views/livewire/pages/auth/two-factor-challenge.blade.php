<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use App\Mail\User\TwoFactor\BackupCodeConsumedMail;
use App\Mail\User\TwoFactor\LowBackupCodesNoticeMail;
use App\Mail\User\TwoFactor\NoBackupCodesRemainingNoticeMail;
use Carbon\Carbon;

new #[Layout('layouts.minimal')] class extends Component {
    public string $code = '';
    public bool $isRecoveryCode = false;
    public ?string $error = null;

    public function mount()
    {
        $user = Auth::user();

        if (!$user || !$user->hasTwoFactorEnabled()) {
            return redirect()->route('overview');
        }

        if ($this->hasValidTwoFactorCookie($user)) {
            return redirect()->route('overview');
        }
    }

    private function hasValidTwoFactorCookie($user): bool
    {
        $twoFactorCookie = request()->cookie('two_factor_verified');

        if (!is_string($twoFactorCookie)) {
            return false;
        }

        try {
            $decryptedToken = decrypt($twoFactorCookie);
            return Hash::check($decryptedToken, $user->getAttribute('two_factor_verified_token'));
        } catch (DecryptException) {
            return false;
        }
    }

    public function submit()
    {
        $user = Auth::user();

        if (!$user) {
            $this->error = 'User not authenticated.';
            return;
        }

        if ($this->isRateLimited($user->id)) {
            $this->handleRateLimited($user->id);
            return;
        }

        if ($user->validateTwoFactorCode($this->code)) {
            $this->handleSuccessfulVerification($user);
        } else {
            $this->handleFailedVerification($user->id);
        }
    }

    private function isRateLimited(int $userId): bool
    {
        return RateLimiter::tooManyAttempts($this->getRateLimitKey($userId), 5);
    }

    private function getRateLimitKey(int $userId): string
    {
        return "two-factor-attempt:{$userId}";
    }

    private function handleRateLimited(int $userId): void
    {
        $seconds = RateLimiter::availableIn($this->getRateLimitKey($userId));
        $this->error = "Too many attempts. Please try again in {$seconds} seconds.";
    }

    private function handleSuccessfulVerification($user): void
    {
        RateLimiter::clear($this->getRateLimitKey($user->id));
        $token = $this->generateSecureToken($user->id);

        Cookie::queue('two_factor_verified', encrypt($token), 30 * 24 * 60, null, null, true, true, false, 'strict');

        $user->update([
            'two_factor_verified_token' => Hash::make($token),
            'last_two_factor_at' => now(),
            'last_two_factor_ip' => request()->ip(),
        ]);

        $unusedCodeCount = $this->getUnusedRecoveryCodeCount($user);

        if ($this->wasRecoveryCodeUsed($user, $this->code)) {
            Mail::to($user)->queue(new BackupCodeConsumedMail($user));
        }

        if ($unusedCodeCount === 0) {
            Mail::to($user)->queue(new NoBackupCodesRemainingNoticeMail($user));
            session()->flash('flash_message', [
                'message' => 'You have no unused recovery codes left. Please generate new ones immediately.',
                'type' => 'warning',
                'dismissible' => true,
            ]);
        } elseif ($unusedCodeCount <= 3) {
            Mail::to($user)->queue(new LowBackupCodesNoticeMail($user));
            session()->flash('flash_message', [
                'message' => "You only have {$unusedCodeCount} unused recovery codes left. Consider generating new ones.",
                'type' => 'warning',
                'dismissible' => true,
            ]);
        }

        $this->redirect(route('overview'));
    }

    private function handleFailedVerification(int $userId): void
    {
        RateLimiter::hit($this->getRateLimitKey($userId));
        usleep(random_int(100000, 300000)); // Sleep for 0.1 to 0.3 seconds to mitigate timing attacks
        $this->error = 'The provided two-factor code or recovery code was invalid.';
    }

    private function generateSecureToken(int $userId): string
    {
        return hash_hmac('sha256', $userId . uniqid('', true), (string)config('app.key'));
    }

    private function getUnusedRecoveryCodeCount($user): int
    {
        $recoveryCodes = $user->getRecoveryCodes();
        return $recoveryCodes->filter(fn($code): bool => $code['used_at'] === null)->count();
    }

    private function wasRecoveryCodeUsed($user, ?string $code): bool
    {
        if (!$code) {
            return false;
        }

        $recoveryCodes = $user->getRecoveryCodes();
        $usedCode = $recoveryCodes->firstWhere('code', $code);

        if (!$usedCode || !isset($usedCode['used_at'])) {
            return false;
        }

        return Carbon::parse($usedCode['used_at'])->isAfter(now()->subSeconds(5));
    }

    public function updatedCode(): void
    {
        if (!$this->isRecoveryCode && strlen($this->code) === 6) {
            $this->submit();
            $this->code = '';
        }
    }

    public function toggleCodeType(): void
    {
        $this->isRecoveryCode = !$this->isRecoveryCode;
        $this->code = '';
        $this->error = null;
    }
};

?>

<div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-8">
        <div class="flex flex-col sm:flex-row items-center justify-between">
            <x-application-logo class="h-11 w-auto fill-current text-primary-950 dark:text-white"/>
            <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4 mt-4 sm:mt-0">
                <img
                    class="h-12 w-12 rounded-full border-2 border-gray-300 dark:border-gray-700"
                    src="{{ Auth::user()->gravatar(60) }}"
                    alt="{{ Auth::user()->first_name }}"
                />
                <span class="text-base font-medium text-gray-700 dark:text-gray-300">
                    {{ Auth::user()->first_name }}
                </span>
            </div>
        </div>
        <div class="mt-8 bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
            @if ($error)
                <div class="rounded-md bg-red-50 dark:bg-red-900 p-4 mb-4">
                    <div class="flex">
                        <div class="text-sm text-red-700 dark:text-red-200">
                            {{ $error }}
                        </div>
                    </div>
                </div>
            @endif

            <div class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                <p class="font-semibold mb-3 text-lg">Welcome back, {{ Auth::user()->first_name }}!</p>
                <p class="mb-3">Please enter your two-factor authentication code.</p>
                <p class="mt-2.5">If you can't access your two-factor device, use a recovery code.</p>
            </div>

            <form wire:submit.prevent="submit" class="space-y-6">
                <div class="flex items-center mb-4">
                    <x-checkbox wire:model="isRecoveryCode" id="recovery-code-toggle" type="checkbox" name="recovery-code-toggle" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                    <label for="recovery-code-toggle" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                        Use recovery code
                    </label>
                </div>

                <div x-show="!$wire.isRecoveryCode">
                    <x-input-label for="code" value="Authentication code"/>
                    <x-text-input
                        name="code"
                        id="code"
                        wire:model.live="code"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        autofocus
                        maxlength="6"
                        class="mt-1 block w-full"
                        placeholder="Enter your 6-digit code"
                    />
                </div>

                <div x-show="$wire.isRecoveryCode">
                    <x-input-label for="recoveryCode" value="Recovery code"/>
                    <x-text-input
                        id="recoveryCode"
                        name="recoveryCode"
                        wire:model="code"
                        type="text"
                        autocomplete="off"
                        class="mt-1 block w-full"
                        placeholder="Enter your recovery code"
                    />
                </div>
                <div>
                    <x-primary-button type="button" wire:click="submit" centered fat action="submit" loadingText="Verifying...">
                        @svg('heroicon-o-finger-print', 'w-5 h-5 mr-2 inline')
                        {{ __('Verify and Proceed') }}
                    </x-primary-button>
                </div>
            </form>
                <div class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                    <p>Having trouble accessing your account? Please refer to our <a href="https://docs.vanguardbackup.com/two-factor-auth#emergency-measures" class="text-gray-950 dark:text-gray-300 font-medium underline transition-all duration-300 ease-in-out hover:text-gray-700 dark:hover:text-gray-100" target="_blank" rel="noopener noreferrer">documentation on emergency measures</a> for assistance.</p>
                </div>
        </div>
    </div>
    <footer class="mt-8 text-center">
        <div class="mt-2 flex justify-center space-x-6">
            <a href="https://github.com/vanguardbackup/vanguard"
               class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300" target="_blank"
               rel="noopener noreferrer">
                <span class="sr-only">GitHub</span>
                <svg height="32" aria-hidden="true" viewBox="0 0 16 16" version="1.1" width="32"
                     data-view-component="true" class="octicon octicon-mark-github v-align-middle">
                    <path fill-rule="evenodd"
                          d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path>
                </svg>
            </a>
        </div>
    </footer>
</div>
