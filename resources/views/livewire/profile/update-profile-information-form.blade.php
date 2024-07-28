<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Masmerise\Toaster\Toaster;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public ?string $gravatar_email;
    public string $timezone = '';
    public ?int $preferred_backup_destination_id = null;
    public string $language = 'en'; // Default language is english.
    public ?bool $receiving_weekly_summary_email = false;

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->gravatar_email = Auth::user()->gravatar_email;
        $this->timezone = Auth::user()->timezone;
        $this->preferred_backup_destination_id = Auth::user()->preferred_backup_destination_id ?? null;
        $this->language = Auth::user()->language;
        $this->receiving_weekly_summary_email = Auth::user()->isOptedInForWeeklySummary();
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'gravatar_email' => ['nullable', 'string', 'lowercase', 'email'],
            'timezone' => ['required', 'string', 'max:255', Rule::in(timezone_identifiers_list())],
            'preferred_backup_destination_id' => ['nullable', 'integer', Rule::exists('backup_destinations', 'id')->where('user_id', $user->id)],
            'receiving_weekly_summary_email' => ['required', 'boolean'],
            'language' => [
                'required',
                'string',
                'min:2',
                'max:3',
                'lowercase',
                'alpha',
                Rule::in(array_keys(config('app.available_languages')))
            ],
        ], $this->messages());

        $user->fill($validated);

        $user->weekly_summary_opt_in_at = $validated['receiving_weekly_summary_email'] ? now() : null;

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Toaster::success(__('Profile details saved.'));
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Please enter a name.'),
            'name.max' => __('Your name is too long. Please try a shorter name.'),
            'email.required' => __('Please enter an email address.'),
            'email.email' => __('Please enter an email address.'),
            'gravatar_email.email' => __('Please enter an email address.'),
            'timezone.required' => __('Please specify a timezone.'),
            'language.required' => __('Please specify a language for your account.')
        ];
    }

    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('overview', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        @if (Auth::user()->canLoginWithGithub())
            <div class="my-4 max-w-md mx-auto">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 p-4 rounded-lg shadow-md border border-gray-200 dark:border-gray-600 transition-all duration-300 hover:shadow-lg hover:scale-[1.02]">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <div class="bg-white dark:bg-gray-700 p-2 rounded-full shadow-sm">
                                <x-icons.github class="w-8 h-8 text-gray-900 dark:text-white transition-colors duration-200"/>
                            </div>
                        </div>
                        <div class="flex-grow">
                <span class="font-medium text-sm sm:text-base text-gray-800 dark:text-gray-100 transition-colors duration-200">
                    {{ __('You can sign in to :app with GitHub.', ['app' => config('app.name')]) }}
                </span>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="bg-green-100 dark:bg-green-800 p-1.5 rounded-full">
                                @svg('heroicon-o-check', 'w-5 h-5 text-green-600 dark:text-green-300 transition-colors duration-200')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="mt-4">
            <div class="mt-4">
                <x-input-label for="avatar" :value="__('Avatar')"/>
                <div class="flex items-center mt-2">
                    <div x-data="{ imageLoaded: false }" class="relative w-20 h-20">
                        <div
                            x-show="!imageLoaded"
                            class="absolute inset-0 bg-gray-200 dark:bg-gray-700 rounded-full animate-pulse"
                        ></div>
                        <img
                            x-on:load="imageLoaded = true"
                            x-bind:class="{ 'opacity-0': !imageLoaded, 'opacity-100': imageLoaded }"
                            class="w-20 h-20 rounded-full transition-opacity duration-300"
                            src="{{ Auth::user()->gravatar('160') }}"
                        />
                    </div>
                    <a href="https://gravatar.com" target="_blank"
                       class="ml-4 text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100 transition-colors ease-in-out">
                        {{ __('Update your avatar on Gravatar') }}
                    </a>
                </div>
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')"/>
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required
                          autofocus autocomplete="name"/>
            <x-input-error class="mt-2" :messages="$errors->get('name')"/>
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')"/>
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required
                          autocomplete="username"/>
            <x-input-error class="mt-2" :messages="$errors->get('email')"/>

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button wire:click.prevent="sendVerification"
                                class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="name" :value="__('Gravatar Email')"/>
            <x-text-input wire:model="gravatar_email" id="gravatar_email" name="name" type="email"
                          class="mt-1 block w-full"
                          autofocus autocomplete="gravatar_email"/>
            <x-input-explain>
                {{ __('Enter an alternative email address to use for your Gravatar picture. If left blank, your primary email will be used.') }}
            </x-input-explain>
            <x-input-error class="mt-2" :messages="$errors->get('gravatar_email')"/>
        </div>

        <div>
            <x-input-label for="timezone" :value="__('Timezone')"/>
            <x-select wire:model="timezone" id="timezone" name="timezone" class="mt-1 block w-full">
                @foreach (formatTimezones() as $identifier => $timezone)
                    <option value="{{ $identifier }}">{{ $timezone }}</option>
                @endforeach
            </x-select>
            <x-input-explain>
                {{ __('Your timezone is used to display dates and times to you in your local time.') }}
            </x-input-explain>
            <x-input-error class="mt-2" :messages="$errors->get('timezone')"/>
        </div>

        <div>
            <x-input-label for="language" :value="__('Language')"/>
            <x-select wire:model.live="language" id="language" name="language" class="mt-1 block w-full">
                @foreach (config('app.available_languages') as $code => $language)
                    <option value="{{ $code }}">{{ $language }}</option>
                @endforeach
            </x-select>
            <x-input-explain>
                {{ __('Please select your preferred language from the dropdown list. This will change the language used throughout the application.') }}
            </x-input-explain>
            <x-input-error class="mt-2" :messages="$errors->get('language')"/>
            @if ($language !== Auth::user()->language)
                <div
                    x-data="{ show: false }"
                    x-show="show"
                    x-transition.opacity.duration.500ms
                    x-init="$nextTick(() => show = true)"
                    class="my-2 text-sm text-blue-600 dark:text-blue-400"
                >
                    {{ __('Please refresh the page after saving to view your new language.') }}
                </div>
            @endif
        </div>

        @if (!Auth::user()->backupDestinations->isEmpty())
            <div>
                <x-input-label for="preferred_backup_destination_id" :value="__('Default Backup Destination')"/>
                <x-select wire:model="preferred_backup_destination_id" id="preferred_backup_destination_id"
                          name="preferred_backup_destination_id" class="mt-1 block w-full">
                    <option value="">{{ __('None') }}</option>
                    @foreach (Auth::user()->backupDestinations as $backupDestination)
                        <option value="{{ $backupDestination->id }}">{{ $backupDestination->label }}
                            - {{ $backupDestination->type() }}</option>
                    @endforeach
                </x-select>
                <x-input-explain>
                    {{ __('The backup destination you select here will be set as the default location for storing new backup tasks.') }}
                </x-input-explain>
                <x-input-error class="mt-2" :messages="$errors->get('preferred_backup_destination_id')"/>
            </div>
        @endif

        <div>
            <x-input-label for="weekly_summary_opt_in" :value="__('Weekly Backup Summary Emails')"/>
            <x-toggle
                name="receiving_weekly_summary_email"
                model="receiving_weekly_summary_email"
            />
            <x-input-explain>
                {{ __('Get a summary of your weekly backup tasks every Monday morning.') }}
            </x-input-explain>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>
                {{ __('Save') }}
            </x-primary-button>
        </div>
    </form>
</section>
