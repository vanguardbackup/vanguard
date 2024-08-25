<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Masmerise\Toaster\Toaster;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public ?string $gravatar_email = null;
    public string $timezone = '';
    public ?int $preferred_backup_destination_id = null;
    public string $language = 'en';
    public bool $receiving_weekly_summary_email = false;
    public int $pagination_count = 15;
    public Collection $pagination_options;

    public string $lastFriday = '';
    public string $lastMonday = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $this->name = $user->name;
        $this->email = $user->email;
        $this->gravatar_email = $user->gravatar_email;
        $this->timezone = $user->timezone;
        $this->preferred_backup_destination_id = $user->preferred_backup_destination_id;
        $this->language = $user->language;
        $this->receiving_weekly_summary_email = $user->isOptedInForWeeklySummary();
        $this->pagination_count = $user->pagination_count ?? 15; // Fallback to 15 if null
        $this->pagination_options = $this->getPaginationOptions();

        $this->setFormattedDates();
    }

    /**
     * Update the user's profile information.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $validated = $this->validate();

        $user->fill($validated);
        $user->weekly_summary_opt_in_at = $validated['receiving_weekly_summary_email'] ? now() : null;

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Toaster::success(__('Profile details saved.'));
    }

    /**
     * Send email verification link.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(route('overview', absolute: false));
            return;
        }

        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, array<int, string|Rule>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore(Auth::id())],
            'gravatar_email' => ['nullable', 'string', 'lowercase', 'email'],
            'timezone' => ['required', 'string', 'max:255', Rule::in(timezone_identifiers_list())],
            'preferred_backup_destination_id' => ['nullable', 'integer', Rule::exists('backup_destinations', 'id')->where('user_id', Auth::id())],
            'receiving_weekly_summary_email' => ['boolean'],
            'pagination_count' => ['required', 'integer', 'min:1', 'max:100', 'in:15,30,50,100'],
            'language' => [
                'required',
                'string',
                'min:2',
                'max:3',
                'lowercase',
                'alpha',
                Rule::in(array_keys(config('app.available_languages')))
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => __('Please enter a name.'),
            'name.max' => __('Your name is too long. Please try a shorter name.'),
            'email.required' => __('Please enter an email address.'),
            'email.email' => __('Please enter a valid email address.'),
            'gravatar_email.email' => __('Please enter a valid email address for Gravatar.'),
            'timezone.required' => __('Please specify a timezone.'),
            'language.required' => __('Please specify a language for your account.'),
            'pagination_count.required' => __('The pagination count is required.'),
            'pagination_count.integer' => __('The pagination count must be an integer.'),
            'pagination_count.min' => __('The pagination count must be at least 1.'),
            'pagination_count.max' => __('The pagination count may not be greater than 100.'),
            'pagination_count.in' => __('The pagination count must be 15, 30, 50, or 100.'),
        ];
    }

    /**
     * Get the pagination options.
     *
     * @return Collection
     */
    private function getPaginationOptions(): Collection
    {
        return collect([15, 30, 50, 100])->mapWithKeys(fn($value) => [$value => "{$value} per page"]);
    }

    /**
     * Set the formatted dates for last Friday and Monday.
     */
    private function setFormattedDates(): void
    {
        $userLanguage = $this->getUserLanguage();
        Carbon::setLocale($userLanguage);

        $this->lastFriday = $this->formatDate(Carbon::FRIDAY);
        $this->lastMonday = $this->formatDate(Carbon::MONDAY);
    }

    /**
     * Get the user's language or fall back to the application default.
     *
     * @return string
     */
    private function getUserLanguage(): string
    {
        $user = Auth::user();

        if (!$user || !$user->language) {
            return App::getLocale();
        }

        return $this->isValidLanguage($user->language)
            ? $user->language
            : App::getLocale();
    }

    /**
     * Check if the given language is supported by the application.
     *
     * @param string $language
     * @return bool
     */
    private function isValidLanguage(string $language): bool
    {
        $availableLanguages = config('app.available_languages', ['en' => 'English']);
        return array_key_exists($language, $availableLanguages);
    }

    /**
     * Format the date for the given day of the week.
     *
     * @param int $day
     * @return string
     */
    private function formatDate(int $day): string
    {
        return Carbon::now()->previous($day)->isoFormat('dddd Do');
    }
}?>
<x-form-wrapper>
    <x-slot name="title">
        {{ __('My Profile') }}
    </x-slot>
    <x-slot name="description">
        {{ __('Customize your account information and preferences.') }}
    </x-slot>
    <x-slot name="icon">
        hugeicons-user
    </x-slot>
    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
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
        <!-- Grid layout for form fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                    <!-- Email verification section -->
                @endif
            </div>

            <div>
                <x-input-label for="gravatar_email" :value="__('Gravatar Email')"/>
                <x-text-input wire:model="gravatar_email" id="gravatar_email" name="gravatar_email" type="email"
                              class="mt-1 block w-full" autofocus autocomplete="gravatar_email"/>
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
                    {{ __('Receive a summary of your weekly backup activities every Monday morning. The upcoming summary will cover all backup tasks from :lastMonday through :lastFriday.', ['lastMonday' => $lastMonday, 'lastFriday' => $lastFriday]) }}
                </x-input-explain>
            </div>

            <div>
                <x-input-label for="pagination_count" :value="__('Items per Page')"/>
                <x-select wire:model="pagination_count" id="pagination_count" name="pagination_count"
                          class="mt-1 block w-full">
                    @foreach ($pagination_options as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-input-explain>
                    {{ __('Select the number of items you want to see per page in lists throughout the application. This setting affects how many backup tasks, servers, and other items are displayed at once.') }}
                </x-input-explain>
                <x-input-error class="mt-2" :messages="$errors->get('pagination_count')"/>
            </div>

        </div>
        <div class="mt-6 pb-4 max-w-3xl mx-auto">
            <div class="flex justify-center">
                <div class="w-full sm:w-4/6">
                    <x-primary-button type="submit" class="w-full justify-center" centered action="submit">
                        {{ __('Save') }}
                    </x-primary-button>
                </div>
            </div>
        </div>
    </form>
</x-form-wrapper>
