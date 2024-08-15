<?php

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
                'description' => __('Use an app like Google Authenticator or Authy.'),
                'icon' => 'heroicon-o-device-phone-mobile',
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
                <x-slot name="title">{{ __('Multi-Factor Authentication (2FA)') }}</x-slot>
                <x-slot name="description">
                    {{ __('Enhance your account security by enabling 2FA. This adds an extra layer of protection to your account.') }}
                </x-slot>
                <x-slot name="icon">heroicon-o-shield-check</x-slot>

                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/50 rounded-lg">
                    <h3 class="text-lg font-medium text-blue-800 dark:text-blue-200 mb-2">{{ __('Why use 2FA?') }}</h3>
                    <ul class="list-disc list-inside text-sm text-blue-700 dark:text-blue-300">
                        <li>{{ __('Adds an extra layer of security to your account') }}</li>
                        <li>{{ __('Protects against unauthorized access even if your password is compromised') }}</li>
                        <li>{{ __('Easy to set up and use') }}</li>
                    </ul>
                </div>

                <div class="space-y-6">
                    @foreach ($this->mfaMethods as $methodKey => $method)
                        <div
                            class="flex items-center p-4 border rounded-lg {{ $currentMethod === $methodKey ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                            <div class="flex-shrink-0 mr-4">
                                @svg($method['icon'], 'w-8 h-8 text-gray-500 dark:text-gray-400')
                            </div>
                            <div class="flex-grow">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $method['name'] }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $method['description'] }}</p>
                            </div>
                            <div class="flex-shrink-0 ml-4">
                                @if ($currentMethod === $methodKey)
                                    <x-danger-button
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'confirm-disable-2fa')"
                                    >
                                        {{ __('Disable') }}
                                    </x-danger-button>
                                @else
                                    <x-primary-button wire:click="startSetup2FA('{{ $methodKey }}')"
                                                      wire:loading.attr="disabled">
                                        {{ __('Configure') }}
                                    </x-primary-button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($currentMethod !== 'none')
                    <div class="mt-8 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('Backup and Recovery') }}</h3>
                        <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 sm:space-x-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ __('Access your backup codes or generate new ones.') }}</p>
                                <div class="flex space-x-2">
                                    <x-secondary-button wire:click="viewBackupCodes">
                                        {{ __('View Backup Codes') }}
                                    </x-secondary-button>
                                    <x-secondary-button wire:click="regenerateBackupCodes">
                                        {{ __('Regenerate Codes') }}
                                    </x-secondary-button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </x-form-wrapper>

            <x-modal name="confirm-disable-2fa" :show="$errors->isNotEmpty()" focusable>
                <x-slot name="title">
                    {{ __('Are you sure you want to disable two-factor authentication?') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('Once your two-factor authentication is disabled, your account will be less secure.') }}
                </x-slot>
                <x-slot name="icon">
                    heroicon-o-trash
                </x-slot>
                <form wire:submit="disable2FA">
                    <div>
                        <x-input-label for="password" value="{{ __('Password') }}" class="sr-only"/>
                        <x-text-input
                            wire:model="password"
                            id="password"
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

                        <x-danger-button class="ml-3">
                            {{ __('Disable') }}
                        </x-danger-button>
                    </div>
                </form>
            </x-modal>

            <x-modal name="confirm-password" :show="$errors->isNotEmpty()" focusable>
                <x-slot name="title">
                    {{ __('Confirm Password') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('Please enter your password to confirm this action.') }}
                </x-slot>
                <x-slot name="icon">
                    heroicon-o-lock-closed
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
                <x-slot name="icon">heroicon-o-device-phone-mobile</x-slot>

                <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
                    <h3 class="text-lg font-medium text-yellow-800 dark:text-yellow-200 mb-2">{{ __('Setup Instructions') }}</h3>
                    <ol class="list-decimal list-inside text-sm text-yellow-700 dark:text-yellow-300 space-y-2">
                        <li>{{ __('Open your authenticator app (e.g., Google Authenticator, Authy)') }}</li>
                        <li>{{ __('Scan the QR code or enter the secret key manually') }}</li>
                        <li>{{ __('Enter the 6-digit code generated by the app below') }}</li>
                        <li>{{ __('Click "Enable" to activate authenticator app 2FA') }}</li>
                    </ol>
                </div>

                <div class="mb-6">
                    <div class="mt-4 max-w-xl text-sm text-gray-600 dark:text-gray-400">
                        <p class="font-semibold">
                            {{ __('Scan the following QR code using your phone\'s authenticator application or enter the setup key.') }}
                        </p>
                    </div>

                    <div class="w-48 h-48 mb-4 md:mb-0 mx-auto">
                        {!! $this->qrCodeSvg !!}
                    </div>

                    <div class="mt-4">
                        <x-input-label for="setup_key" :value="__('Secret Key')"/>
                        <div class="flex mt-1">
                            <x-text-input id="setup_key" type="text" name="setup_key" class="mt-1 block w-full"
                                          :value="$this->twoFactorSecret" readonly/>
                            <x-secondary-button class="ml-3" wire:click="copySetupKey" type="button">
                                {{ __('Copy') }}
                            </x-secondary-button>
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
        @elseif ($currentView === 'success')
            <x-form-wrapper>
                <x-slot name="title">{{ __('2FA Method Enabled!') }}</x-slot>
                <x-slot name="description">
                    {{ __('Your two-factor authentication method has been successfully enabled.') }}
                </x-slot>
                <x-slot name="icon">heroicon-o-check-circle</x-slot>

                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900 rounded-lg">
                    <h3 class="text-lg font-medium text-green-800 dark:text-green-200 mb-2">{{ __('Next Steps') }}</h3>
                    <ul class="list-disc list-inside text-sm text-green-700 dark:text-green-300 space-y-2">
                        <li>{{ __('Store your backup codes in a secure location') }}</li>
                        <li>{{ __('Review your account security settings regularly') }}</li>
                    </ul>
                </div>

                <div class="flex justify-center space-x-4">
                    <x-primary-button wire:click="viewBackupCodes">
                        {{ __('View Backup Codes') }}
                    </x-primary-button>
                    <x-secondary-button wire:click="goBackToMethodsView">
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
                <x-slot name="icon">heroicon-o-key</x-slot>

                <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/50 rounded-lg">
                    <h3 class="text-lg font-medium text-yellow-800 dark:text-yellow-200 mb-2">{{ __('Important:') }}</h3>
                    <ul class="list-disc list-inside text-sm text-yellow-700 dark:text-yellow-300 space-y-2">
                        <li>{{ __('Each code can only be used once') }}</li>
                        <li>{{ __('Store these codes in a secure password manager or print them') }}</li>
                        <li>{{ __('Regenerating codes will invalidate all previous codes') }}</li>
                    </ul>
                </div>

                <div class="mt-4 bg-gray-100 dark:bg-gray-800 p-4 rounded-md">
                    <ul class="list-none grid grid-cols-2 gap-2">
                        @foreach ($backupCodes as $backupCode)
                            <li class="text-sm font-mono bg-white dark:bg-gray-700 p-2 rounded
                        {{ $backupCode['used_at'] ? 'line-through text-red-500 dark:text-red-400' : '' }}">
                                {{ $backupCode['code'] }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                    <div class="flex space-x-4">
                        <x-secondary-button wire:click="downloadBackupCodes">
                            {{ __('Download Codes') }}
                        </x-secondary-button>
                        <x-secondary-button wire:click="regenerateBackupCodes">
                            {{ __('Regenerate Codes') }}
                        </x-secondary-button>
                    </div>
                    <x-primary-button wire:click="confirmBackupCodes">
                        {{ __('I Have Saved These Codes') }}
                    </x-primary-button>
                </div>

                <div class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Make sure to store these codes securely.') }}
                </div>
            </x-form-wrapper>
        <x-modal name="confirm-regenerate" :show="$errors->isNotEmpty()" focusable>
            <x-slot name="title">
                {{ __('Regenerate Backup Codes') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Are you sure you want to regenerate your backup codes? All existing codes will be invalidated.') }}
            </x-slot>
            <x-slot name="icon">
                heroicon-o-exclamation-triangle
            </x-slot>
            <form wire:submit="performRegenerateBackupCodes">
                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button class="ml-3">
                        {{ __('Regenerate') }}
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
