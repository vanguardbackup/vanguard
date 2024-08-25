<?php

use App\Mail\User\TwoFactor\DisabledMail;
use App\Mail\User\TwoFactor\EnabledMail;
use App\Mail\User\TwoFactor\RegeneratedBackupCodesMail;
use App\Mail\User\TwoFactor\ViewedBackupCodesMail;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Volt\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component {

    use WithRateLimiting;

    public string $currentView = 'methods';
    public string $currentMethod = 'none';
    public Collection $backupCodes;

    #[Rule('required|string')]
    public string $password = '';

    public bool $showingRecoveryCodes = false;

    #[Rule('required|string|size:6')]
    public string $verificationCode = '';

    public ?string $qrCodeSvg = null;
    public ?string $twoFactorSecret = null;

    public string $confirmationAction = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->currentMethod = $user->hasTwoFactorEnabled() ? User::TWO_FACTOR_APP : 'none';
        $this->showingRecoveryCodes = $user->hasTwoFactorEnabled();
    }

    #[Computed]
    public function mfaMethods(): array
    {
        return [
            User::TWO_FACTOR_APP => [
                'name' => __('Authenticator App'),
                'description' => __('Use a mobile app to generate secure, time-based codes for login.'),
                'icon' => 'hugeicons-smart-phone-01',
                'benefits' => [
                    __('Functions without internet or mobile network connection'),
                    __('Allows management of multiple accounts across various services'),
                    __('Widely accepted and compatible with most online platforms'),
                ],
            ],
        ];
    }

    public function startSetup2FA(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $twoFactorAuth = $user->createTwoFactorAuth();
        $this->qrCodeSvg = $twoFactorAuth->toQr();
        $this->twoFactorSecret = $twoFactorAuth->toString();

        $this->currentView = 'setup-app';
    }

    public function verifyAndEnable2FA(): void
    {
        $this->validate([
            'verificationCode' => ['required', 'string', 'size:6'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!$user->confirmTwoFactorAuth($this->verificationCode)) {
            $this->addError('verificationCode', __('The provided two factor authentication code was invalid.'));
            return;
        }

        $token = Str::random(40);
        $user->update([
            'last_two_factor_ip' => request()->ip(),
            'last_two_factor_at' => now(),
            'two_factor_enabled_at' => now(),
            'two_factor_verified_token' => Hash::make($token),
        ]);

        Cookie::queue('two_factor_verified', encrypt($token), 60 * 24 * 30); // 30 days

        $this->currentView = 'success';
        $this->showingRecoveryCodes = true;
        $this->currentMethod = 'app';
        $this->backupCodes = $user->getRecoveryCodes();
        Mail::to($user)->queue(new EnabledMail($user));
    }

    public function disable2FA(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->disableTwoFactorAuth();

        $this->currentMethod = 'none';
        $this->showingRecoveryCodes = false;
        $this->currentView = 'methods';
        $this->password = '';

        Cookie::queue(Cookie::forget('two_factor_verified'));

        $this->dispatch('close-modal', 'confirm-disable-2fa');

        Mail::to($user)->queue(new DisabledMail($user));
        Toaster::success('Two-factor authentication has been disabled.');
    }

    public function goBackToMethodsView(): void
    {
        $this->currentView = 'methods';
        $this->verificationCode = '';
    }

    public function confirmBackupCodes(): void
    {
        $this->currentView = 'methods';
    }

    public function viewBackupCodes(): void
    {
        if ($this->currentView === 'methods') {
            $this->confirmationAction = 'viewBackupCodes';
            $this->dispatch('open-modal', 'confirm-password');
        } else {
            $this->loadBackupCodes();
        }
    }

    public function loadBackupCodes(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->backupCodes = $user->getRecoveryCodes();
        $this->currentView = 'backup-codes';
        Mail::to($user)->queue(new ViewedBackupCodesMail($user));
    }

    public function regenerateBackupCodes(): void
    {
        if ($this->currentView === 'backup-codes') {
            $this->dispatch('open-modal', 'confirm-regenerate');
        } else {
            $this->confirmationAction = 'regenerateBackupCodes';
            $this->dispatch('open-modal', 'confirm-password');
        }
    }

    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        if ($this->confirmationAction === 'viewBackupCodes') {
            $this->loadBackupCodes();
        } elseif ($this->confirmationAction === 'regenerateBackupCodes') {
            $this->performRegenerateBackupCodes();
        }

        $this->password = '';
        $this->dispatch('close-modal', 'confirm-password');
    }

    public function performRegenerateBackupCodes(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->hasTwoFactorEnabled()) {
            Toaster::error('Two-factor authentication is not enabled.');
            return;
        }

        $this->backupCodes = $user->generateRecoveryCodes();
        $this->dispatch('close-modal', 'confirm-regenerate');
        Mail::to($user)->queue(new RegeneratedBackupCodesMail($user));
        Toaster::success('Your backup codes have been regenerated.');
    }

    #[Computed]
    public function qrCodeSvg(): ?string
    {
        /** @var User $user */
        $user = Auth::user();
        return $user->createTwoFactorAuth()->toQr();
    }

    #[Computed]
    public function twoFactorSecret(): ?string
    {
        /** @var User $user */
        $user = Auth::user();
        return $user->createTwoFactorAuth()->toString();
    }

    public function copySetupKey(): void
    {
        $this->dispatch('copy-to-clipboard', text: $this->twoFactorSecret);
        Toaster::success('Copied secret key to your clipboard.');
    }

    public function downloadBackupCodes(): ?StreamedResponse
    {
        $user = Auth::user();
        $backupCodes = $user?->getRecoveryCodes();

        if (!$backupCodes) {
            Toaster::error('No backup codes available.');
            return null;
        }

        $content = "Your Vanguard Backup Codes:\n\n";

        foreach ($backupCodes as $backupCode) {
            $code = $backupCode['code'];
            $used = $backupCode['used_at'] ? ' (Used)' : '';
            $content .= $code . $used . "\n";
        }

        Toaster::info('Preparing to download your backup codes.');
        $this->dispatch('download');
        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'vanguard-backup-codes.txt');
    }
}
?>

<div wire:key="{{ auth()->id() }}-two-factor-auth">
    <div wire:key="current-view-{{ $currentView }}">
        @if ($currentView === 'methods')
            <x-form-wrapper>
                <x-slot name="title">{{ __('Two-Factor Authentication') }}</x-slot>
                <x-slot name="description">
                    {{ __('Enhance your account security by enabling Two-Factor Authentication.') }}
                </x-slot>
                <x-slot name="icon">hugeicons-square-lock-02</x-slot>

                <div class="space-y-6">
                    @foreach ($this->mfaMethods as $methodKey => $method)
                        <div
                            class="border border-gray-200 dark:border-gray-600 rounded-lg transition-all duration-200 overflow-hidden">
                            <div class="p-6">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center mb-4 sm:mb-0">
                                        <div class="flex-shrink-0 mr-4">
                                            @svg($method['icon'], 'w-10 h-10 text-gray-500 dark:text-gray-400')
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $method['name'] }}</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $method['description'] }}</p>
                                        </div>
                                    </div>
                                    <div class="flex justify-end sm:ml-4 sm:flex-shrink-0">
                                        @if ($currentMethod === $methodKey)
                                            <x-danger-button
                                                x-data=""
                                                x-on:click.prevent="$dispatch('open-modal', 'confirm-disable-2fa')"
                                                class="w-full sm:w-auto justify-center"
                                            >
                                                {{ __('Disable') }}
                                            </x-danger-button>
                                        @else
                                            <x-primary-button
                                                wire:click="startSetup2FA('{{ $methodKey }}')"
                                                wire:loading.attr="disabled"
                                                class="w-full sm:w-auto justify-center"
                                            >
                                                {{ __('Configure') }}
                                            </x-primary-button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if (isset($method['benefits']))
                                <div
                                    class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Benefits:') }}</h4>
                                    <ul class="space-y-1">
                                        @foreach ($method['benefits'] as $benefit)
                                            <li class="flex items-start">
                                                @svg('hugeicons-tick-01', 'w-5 h-5 text-green-500 mr-2 flex-shrink-0')
                                                <span
                                                    class="text-sm text-gray-600 dark:text-gray-400">{{ $benefit }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($currentMethod !== 'none')
                    <div class="mt-8 p-6 bg-gray-100 dark:bg-gray-800 rounded-lg shadow-sm">
                        <div class="flex items-center mb-4">
                            @svg('hugeicons-security-lock', 'w-8 h-8 text-gray-500 dark:text-gray-400 mr-3')
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('Backup and Recovery') }}</h3>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('Access your backup codes or generate new ones for account recovery.') }}</p>
                        <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                            <x-secondary-button wire:click="viewBackupCodes" class="justify-center">
                                @svg('hugeicons-eye', 'w-5 h-5 mr-2')
                                {{ __('View Backup Codes') }}
                            </x-secondary-button>
                            <x-secondary-button wire:click="regenerateBackupCodes" class="justify-center">
                                @svg('hugeicons-refresh', 'w-5 h-5 mr-2')
                                {{ __('Regenerate Codes') }}
                            </x-secondary-button>
                        </div>
                    </div>
                @endif
            </x-form-wrapper>

            <x-modal name="confirm-disable-2fa" :show="$errors->isNotEmpty()" focusable>
                <x-slot name="title">
                    {{ __('Disable Two-Factor Authentication') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('Please review the consequences before proceeding.') }}
                </x-slot>
                <x-slot name="icon">
                    hugeicons-square-lock-password
                </x-slot>

                <div class="mb-6">
                    <div class="flex items-center mb-4 text-yellow-600 dark:text-yellow-500">
                        @svg('hugeicons-alert-02', 'w-6 h-6 mr-2')
                        <h3 class="text-lg font-semibold">{{ __('Warning: Reduced Security') }}</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        {{ __('Disabling two-factor authentication will significantly reduce the security of your account. Please consider the following consequences:') }}
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        <li>{{ __('Your account will be protected by password only') }}</li>
                        <li>{{ __('Increased vulnerability to unauthorized access') }}</li>
                        <li>{{ __('Loss of additional layer of security for sensitive operations') }}</li>
                        <li>{{ __('Your backup codes will be invalidated') }}</li>
                    </ul>
                </div>

                <form wire:submit="disable2FA">
                    <div>
                        <x-input-label for="password" value="{{ __('Confirm Your Password') }}"/>
                        <x-text-input
                            wire:model="password"
                            id="password"
                            name="password"
                            type="password"
                            class="mt-1 block w-full"
                            placeholder="{{ __('Enter your current password') }}"
                            autofocus
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-2"/>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <x-secondary-button x-on:click="$dispatch('close')">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                        <x-danger-button>
                            {{ __('Disable 2FA') }}
                        </x-danger-button>
                    </div>
                </form>

                <div class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Note: You can re-enable two-factor authentication at any time to improve your account security.') }}
                </div>
            </x-modal>

            <x-modal name="confirm-password" :show="$errors->isNotEmpty()" focusable>
                <x-slot name="title">
                    {{ __('Confirm Password') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('Please enter your password to confirm this action.') }}
                </x-slot>
                <x-slot name="icon">
                    hugeicons-signature
                </x-slot>
                <form wire:submit="confirmPassword">
                    <div>
                        <x-input-label for="confirm-password" value="{{ __('Password') }}" class="sr-only"/>
                        <x-text-input
                            wire:model="password"
                            id="confirm-password"
                            name="password"
                            type="password"
                            class="mt-1 block w-full"
                            placeholder="{{ __('Password') }}"
                            autofocus
                        />

                        <x-input-error :messages="$errors->get('password')" class="mt-2"/>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-secondary-button x-on:click="$dispatch('close')">
                            {{ __('Cancel') }}
                        </x-secondary-button>

                        <x-primary-button class="ml-3">
                            {{ __('Confirm') }}
                        </x-primary-button>
                    </div>
                </form>
            </x-modal>
        @elseif ($currentView === 'setup-app')
            <x-form-wrapper>
                <x-slot name="title">{{ __('Authenticator App 2FA') }}</x-slot>
                <x-slot name="description">
                    {{ __('Secure your account using an authenticator app for two-factor authentication.') }}
                </x-slot>
                <x-slot name="icon">hugeicons-smart-phone-01</x-slot>

                <div x-data="{ open: false }" class="mb-6 border-b border-gray-200 dark:border-gray-600">
                    <button @click="open = !open" class="flex justify-between items-center w-full p-6 text-left">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('Two-Factor Authentication Setup Instructions') }}</h3>
                        <svg x-show="!open" class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                        <svg x-show="open" class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                        </svg>
                    </button>

                    <div x-show="open" x-collapse>
                        <div class="p-6">
                            <ol class="list-none space-y-6">
                                <li class="flex items-start">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 mr-4 flex-shrink-0 text-lg font-semibold">1</span>
                                    <div>
                                        <p class="text-gray-700 dark:text-gray-300 mb-3">{{ __('Install and open an authenticator app on your device:') }}</p>
                                        <div class="flex flex-wrap gap-4">
                                            <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2"
                                               target="_blank" rel="noopener noreferrer"
                                               class="flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-300">
                                                <x-icons.google-auth
                                                    class="w-6 h-6 mr-2 text-gray-600 dark:text-gray-100 fill-current"/>
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Google Authenticator</span>
                                            </a>
                                            <a href="https://authy.com/download/" target="_blank" rel="noopener noreferrer"
                                               class="flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-300">
                                                <x-icons.authy
                                                    class="w-6 h-6 mr-2 text-gray-600 dark:text-gray-300 fill-current"/>
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Authy</span>
                                            </a>
                                            <a href="https://1password.com/downloads/" target="_blank" rel="noopener noreferrer"
                                               class="flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-300">
                                                <x-icons.onepassword
                                                    class="w-6 h-6 mr-2 text-gray-600 dark:text-gray-300 fill-current"/>
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">1Password</span>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 mr-4 flex-shrink-0 text-lg font-semibold">2</span>
                                    <p class="text-gray-700 dark:text-gray-300">{{ __('In your authenticator app, add a new account by scanning the QR code below or manually entering the provided secret key') }}</p>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 mr-4 flex-shrink-0 text-lg font-semibold">3</span>
                                    <p class="text-gray-700 dark:text-gray-300">{{ __('Once added, your app will display a 6-digit code that changes every 30 seconds') }}</p>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 mr-4 flex-shrink-0 text-lg font-semibold">4</span>
                                    <p class="text-gray-700 dark:text-gray-300">{{ __('Enter the current 6-digit code from your app in the verification field below, then click "Enable Two-Factor Auth"') }}</p>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="mt-4 max-w-xl text-sm text-gray-600 dark:text-gray-400">
                        <p class="font-semibold">
                            {{ __('Scan the QR code or enter the secret key in your authenticator app.') }}
                        </p>
                    </div>
                    <div class="flex flex-col lg:flex-row items-center justify-between max-w-5xl mx-auto mt-8 space-y-8 lg:space-y-0 lg:space-x-8">
                        <div class="w-72 h-72 flex-shrink-0">
                            <div id="qr-code-container"
                                 class="w-full h-full flex items-center justify-center rounded-lg overflow-hidden">
                                <div class="qr-code-wrapper p-2">
                                    {!! $this->qrCodeSvg !!}
                                </div>
                            </div>
                        </div>

                        <div class="w-full lg:w-2/3">
                            <x-input-label for="setup_key" :value="__('Secret Key')" class="mb-3 text-lg font-medium"/>
                            <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3">
                                <x-text-input
                                    id="setup_key"
                                    type="text"
                                    name="setup_key"
                                    class="block w-full text-lg py-2 px-3"
                                    :value="$this->twoFactorSecret"
                                    readonly
                                />
                                <x-secondary-button
                                    wire:click="copySetupKey"
                                    type="button"
                                    class="whitespace-nowrap justify-center inline-flex items-center px-4 py-2"
                                >
                                    {{ __('Copy') }}
                                    @svg('hugeicons-task-add-01', 'w-5 h-5 ml-2')
                                </x-secondary-button>
                            </div>
                        </div>
                    </div>
                </div>

                <form wire:submit.prevent="verifyAndEnable2FA" class="space-y-6">
                    <div>
                        <x-input-label for="verification_code" :value="__('Verification Code')"/>
                        <x-text-input id="verification_code" type="text" wire:model="verificationCode"
                                      name="verificationCode" required autofocus class="mt-1 block w-full"/>
                        <x-input-error :messages="$errors->get('verificationCode')" class="mt-2"/>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <x-secondary-button wire:click="goBackToMethodsView" type="button">
                            {{ __('Cancel') }}
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            {{ __('Enable') }}
                        </x-primary-button>
                    </div>
                </form>
            </x-form-wrapper>

            <style>
                #qr-code-container {
                    background-color: white; /* Ensure white background in light mode */
                }

                .qr-code-wrapper {
                    width: 100%;
                    height: 100%;
                    max-width: 192px;
                    max-height: 192px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .qr-code-wrapper svg {
                    width: 90%; /* Slightly smaller to create some padding */
                    height: 90%;
                    max-width: 172px; /* 90% of 192px */
                    max-height: 172px;
                }

                /* Dark mode styles */
                @media (prefers-color-scheme: dark) {
                    #qr-code-container {
                        background-color: #1f2937; /* dark:bg-gray-800 equivalent */
                    }

                    .qr-code-wrapper svg {
                        filter: invert(1);
                    }
                }
            </style>
        @elseif ($currentView === 'success')
            <x-form-wrapper>
                <x-slot name="title">{{ __('2FA Method Enabled!') }}</x-slot>
                <x-slot name="description">
                    {{ __('Your two-factor authentication method has been successfully enabled.') }}
                </x-slot>
                <x-slot name="icon">hugeicons-checkmark-circle-02</x-slot>

                <div class="mb-8 p-6 bg-white dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('Success!') }}</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        {{ __('Your account is now more secure with two-factor authentication. Here are some important next steps:') }}
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span
                                class="text-gray-700 dark:text-gray-300">{{ __('Store your backup codes in a secure location (e.g., password manager)') }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span
                                class="text-gray-700 dark:text-gray-300">{{ __('Review your account security settings regularly') }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-6 h-6 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span
                                class="text-gray-700 dark:text-gray-300">{{ __('Consider enabling 2FA on other important accounts') }}</span>
                        </li>
                    </ul>
                </div>

                <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <x-primary-button wire:click="viewBackupCodes" class="w-full sm:w-auto justify-center">
                        @svg('hugeicons-matrix', 'w-5 h-5 mr-2 inline')
                        {{ __('View Backup Codes') }}
                    </x-primary-button>
                    <x-secondary-button wire:click="goBackToMethodsView" class="w-full sm:w-auto justify-center">
                        @svg('hugeicons-arrow-left-02', 'w-5 h-5 mr-2 inline')
                        {{ __('Back to 2FA Methods') }}
                    </x-secondary-button>
                </div>
            </x-form-wrapper>
        @elseif ($currentView === 'backup-codes')
            <x-form-wrapper>
                <x-slot name="title">{{ __('Backup Codes') }}</x-slot>
                <x-slot name="description">
                    {{ __('Store these backup codes in a secure location. They can be used to access your account if you lose access to your primary 2FA method.') }}
                </x-slot>
                <x-slot name="icon">hugeicons-matrix</x-slot>

                <div class="mb-8 p-6">
                    <div class="flex items-center mb-4">
                        @svg('hugeicons-alert-02', 'w-8 h-8 text-yellow-500 mr-3')
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('Important:') }}</h3>
                    </div>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            @svg('hugeicons-alert-02', 'w-5 h-5 text-yellow-500 mr-2 mt-0.5 flex-shrink-0')
                            <span
                                class="text-gray-700 dark:text-gray-300">{{ __('Each code can only be used once') }}</span>
                        </li>
                        <li class="flex items-start">
                            @svg('hugeicons-alert-02', 'w-5 h-5 text-yellow-500 mr-2 mt-0.5 flex-shrink-0')
                            <span
                                class="text-gray-700 dark:text-gray-300">{{ __('Store these codes in a secure password manager or print them') }}</span>
                        </li>
                        <li class="flex items-start">
                            @svg('hugeicons-alert-02', 'w-5 h-5 text-yellow-500 mr-2 mt-0.5 flex-shrink-0')
                            <span
                                class="text-gray-700 dark:text-gray-300">{{ __('Regenerating codes will invalidate all previous codes') }}</span>
                        </li>
                    </ul>
                </div>

                <div class="mb-8 bg-gray-100 dark:bg-gray-800 p-6 rounded-lg shadow-inner">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Your Backup Codes') }}</h4>
                    <ul class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach ($backupCodes as $backupCode)
                            <li class="relative">
                                <div
                                    class="font-mono text-sm bg-white dark:bg-gray-700 p-3 rounded-md shadow {{ $backupCode['used_at'] ? 'opacity-50' : '' }}">
                                    {{ $backupCode['code'] }}
                                </div>
                                @if ($backupCode['used_at'])
                                    <span
                                        class="absolute top-0 right-0 bg-red-500 text-white text-xs px-2 py-1 rounded-bl-md rounded-tr-md">{{ __('Used') }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                        <x-secondary-button wire:click="downloadBackupCodes" class="w-full sm:w-auto justify-center">
                            @svg('hugeicons-download-04', 'w-5 h-5 mr-2 inline')
                            {{ __('Download Codes') }}
                        </x-secondary-button>
                        <x-secondary-button wire:click="regenerateBackupCodes" class="w-full sm:w-auto justify-center">
                            @svg('hugeicons-refresh', 'w-5 h-5 mr-2 inline')
                            {{ __('Regenerate Codes') }}
                        </x-secondary-button>
                    </div>
                    <x-primary-button wire:click="confirmBackupCodes" class="w-full sm:w-auto justify-center">
                        @svg('hugeicons-checkmark-circle-02', 'w-5 h-5 mr-2 inline')
                        {{ __('I Have Saved These Codes') }}
                    </x-primary-button>
                </div>

                <div class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Make sure to store these codes securely. They are your backup access to your account.') }}
                </div>
            </x-form-wrapper>
            <x-modal name="confirm-regenerate" :show="$errors->isNotEmpty()" focusable>
                <x-slot name="title">
                    {{ __('Regenerate Two-Factor Authentication Backup Codes') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('You are about to regenerate your two-factor authentication (2FA) backup codes. Please review the following information:') }}
                </x-slot>
                <x-slot name="icon">
                    hugeicons-alert-02
                </x-slot>

                <div class="mt-4 mb-6">
                    <ul class="list-disc list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li>{{ __('All existing backup codes will be immediately invalidated.') }}</li>
                        <li>{{ __('New backup codes will be generated for your account.') }}</li>
                        <li>{{ __('You should save or print the new codes in a secure location.') }}</li>
                    </ul>
                </div>

                <p class="mb-4 font-medium text-gray-800 dark:text-gray-200">
                    {{ __('Are you sure you want to proceed with regenerating your backup codes?') }}
                </p>

                <form wire:submit="performRegenerateBackupCodes">
                    <div class="mt-6 flex justify-end">
                        <x-secondary-button x-on:click="$dispatch('close')">
                            {{ __('Cancel') }}
                        </x-secondary-button>

                        <x-danger-button class="ml-3">
                            {{ __('Yes, Regenerate Codes') }}
                        </x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('copy-to-clipboard', (event) => {
                const text = event.text;
                navigator.clipboard.writeText(text).then(() => {
                }, (err) => {
                    console.error('Could not copy text: ', err);
                });
            });
        });
    </script>
</div>
