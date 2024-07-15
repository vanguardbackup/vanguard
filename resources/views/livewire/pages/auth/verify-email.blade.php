<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Carbon\Carbon;

new #[Layout('layouts.guest')] class extends Component {
    public string $userEmail = '';
    public string $emailProvider = '';
    public string $emailLink = '';
    public ?Carbon $lastVerificationSentAt = null;
    public int $resendCooldown = 60; // Cooldown in seconds

    public function mount(): void
    {
        $this->userEmail = Auth::user()->email;
        $this->determineEmailProvider();
        $this->lastVerificationSentAt = session('last_verification_sent_at');
    }

    public function determineEmailProvider(): void
    {
        $userEmail = filter_var($this->userEmail, FILTER_SANITIZE_EMAIL);

        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            $this->emailProvider = '';
            $this->emailLink = '';
            return;
        }

        $domain = strtolower(explode('@', $userEmail)[1] ?? '');
        $providers = [
            'gmail.com' => ['name' => 'Gmail', 'link' => 'https://mail.google.com/'],
            'outlook.com' => ['name' => 'Outlook', 'link' => 'https://outlook.live.com/'],
            'hotmail.com' => ['name' => 'Outlook', 'link' => 'https://outlook.live.com/'],
            'live.com' => ['name' => 'Outlook', 'link' => 'https://outlook.live.com/'],
            'yahoo.com' => ['name' => 'Yahoo', 'link' => 'https://mail.yahoo.com/'],
            'protonmail.com' => ['name' => 'ProtonMail', 'link' => 'https://mail.proton.me/'],
            'icloud.com' => ['name' => 'iCloud', 'link' => 'https://www.icloud.com/mail'],
        ];

        if (isset($providers[$domain])) {
            $this->emailProvider = $providers[$domain]['name'];
            $this->emailLink = $providers[$domain]['link'];
        } else {
            $this->emailProvider = '';
            $this->emailLink = '';
        }
    }

    public function getGravatarUrl(): string
    {
        $hash = md5(strtolower(trim($this->userEmail)));
        return "https://www.gravatar.com/avatar/{$hash}?s=300&d=mp";
    }

    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(route('overview', absolute: false), true);
            return;
        }

        if ($this->lastVerificationSentAt && $this->lastVerificationSentAt->diffInSeconds(now()) < $this->resendCooldown) {
            $this->addError('cooldown', __('Please wait before requesting another verification email.'));
            return;
        }

        Auth::user()->sendEmailVerificationNotification();
        $this->lastVerificationSentAt = now();
        session(['last_verification_sent_at' => $this->lastVerificationSentAt]);
        Session::flash('status', 'verification-link-sent');
    }

    public function getCooldownRemaining(): int
    {
        if (!$this->lastVerificationSentAt) {
            return 0;
        }
        $remaining = $this->resendCooldown - $this->lastVerificationSentAt->diffInSeconds(now());
        return max(0, $remaining);
    }

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', true);
    }
}

?>

<div>
    <x-slot name="title">
        {{ __('Verify Email Address') }}
    </x-slot>
    <x-slot name="description">
        {{ __('Please verify your email address to continue.') }}
    </x-slot>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')"/>
    <x-auth-session-error class="mb-4" :loginError="session('loginError')"/>

    <div class="mt-8 space-y-6">
        <!-- User Avatar and Name -->
        <div class="flex items-center justify-center mb-6">
            <div class="flex flex-col items-center">
                <img class="w-20 h-20 rounded-full mb-2" src="{{ $this->getGravatarUrl() }}"
                     alt="{{ Auth::user()->name }}'s avatar">
                <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">{{ Auth::user()->name }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $this->userEmail }}</span>
            </div>
        </div>

        <div class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </div>

        @if ($this->emailLink)
            <div class="text-center mt-4">
                <a href="{{ $this->emailLink }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-600 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-300 disabled:opacity-25 transition">
                    {{ __('Open :provider Inbox', ['provider' => $this->emailProvider]) }}
                    @svg('heroicon-o-arrow-top-right-on-square', 'w-4 h-4 ml-2')
                </a>
            </div>
        @else
            <div class="text-center mt-4 text-sm text-gray-500 dark:text-gray-400">
                {{ __("We couldn't detect your email provider. Please check your email inbox manually.") }}
            </div>
        @endif

        @if (session('status') === 'verification-link-sent')
            <div class="font-medium text-sm text-green-600 dark:text-green-400 text-center">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </div>
        @endif

        @error('cooldown')
        <div class="font-medium text-sm text-red-600 dark:text-red-400 text-center">
            {{ $message }}
        </div>
        @enderror

        <x-primary-button
            wire:click="sendVerification"
            class="w-full justify-center rounded-md border border-transparent bg-primary-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-600"
            :disabled="$this->getCooldownRemaining() > 0"
        >
            @if ($this->getCooldownRemaining() > 0)
                {{ __('Resend Verification Email') }} ({{ $this->getCooldownRemaining() }}s)
            @else
                {{ __('Resend Verification Email') }}
            @endif
            @svg('heroicon-o-arrow-right', 'w-5 h-5 ms-2 inline')
        </x-primary-button>

        <div class="text-center mt-6">
            <button wire:click="logout" type="button"
                    class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                {{ __('Sign Out') }}
            </button>
        </div>
    </div>
</div>
