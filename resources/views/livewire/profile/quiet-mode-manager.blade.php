<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

new class extends Component {
    public ?string $quietUntilDate = null;
    public array $suggestedDates = [
        '7' => '1 week',
        '14' => '2 weeks',
        '30' => '1 month',
    ];
    public bool $isFaqOpen = false;

    public function mount(): void
    {
        $this->refreshQuietUntilDate();
    }

    private function refreshQuietUntilDate(): void
    {
        $user = Auth::user();
        $this->quietUntilDate = $user->quiet_until ? $user->quiet_until->format('Y-m-d') : null;
    }

    #[Computed]
    public function isQuietModeActive(): bool
    {
        return Auth::user()->quiet_until && Auth::user()->quiet_until->isFuture();
    }

    #[Computed]
    public function daysLeft(): int
    {
        if (! $this->isQuietModeActive) {
            return 0;
        }
        return max(0, now()->diffInDays(Auth::user()->quiet_until, false));
    }

    public function selectSuggestedDate(int $days): void
    {
        $this->quietUntilDate = now()
            ->addDays($days)
            ->format('Y-m-d');
    }

    public function enableQuietMode(): void
    {
        $this->validate(
            [
                'quietUntilDate' => 'required|date|after:today',
            ],
            [
                'quietUntilDate.required' => __(
                    'Please specify the duration for which notifications should be silenced.',
                ),
                'quietUntilDate.after' => __('The quiet mode end date must be after today.'),
            ],
        );

        $user = Auth::user();
        $user->quiet_until = Carbon::parse($this->quietUntilDate)->endOfDay();
        $user->save();

        $this->refreshQuietUntilDate();

        $friendlyDate = Carbon::parse($this->quietUntilDate)->format('l, F j');
        Toaster::success(
            __("Shh... Quiet Mode activated! You'll have peace until :date.", [
                'date' => $friendlyDate,
            ]),
        );
    }

    public function disableQuietMode(): void
    {
        $user = Auth::user();
        $user->quiet_until = null;
        $user->save();

        $this->quietUntilDate = null;
        Toaster::success(__('Welcome back! Quiet Mode is now off.'));
    }

    public function toggleFaq(): void
    {
        $this->isFaqOpen = ! $this->isFaqOpen;
    }
};

?>

<div>
    <x-form-wrapper>
        <x-slot name="title">{{ __('Manage Quiet Mode') }}</x-slot>
        <x-slot name="description">
            {{ __('Manage your Quiet Mode settings to temporarily pause notifications.') }}
        </x-slot>
        <x-slot name="icon">hugeicons-notification-snooze-02</x-slot>

        <div class="mb-6 p-4">
            <h4 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ __('What is Quiet Mode?') }}
            </h4>
            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <p>
                    {{ __('Quiet Mode temporarily pauses most notifications from our platform.') }}
                </p>
                <p>{{ __('It\'s ideal for:') }}</p>
                <ul class="list-inside list-disc pl-4">
                    <li>{{ __('Vacations') }}</li>
                    <li>{{ __('Taking a break from updates') }}</li>
                </ul>
                <p>
                    {{ __('While active, we pause your backup task notifications.') }}
                </p>
                <p>
                    {{ __('You can set a specific duration for Quiet Mode.') }}
                </p>
                <p>
                    {{ __('We\'ll email you when your Quiet Mode is about to expire.') }}
                </p>
            </div>
        </div>

        <div class="mb-6 space-y-6 p-4">
            <div>
                <div class="mb-4 flex items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('Quiet Mode Status') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            @if ($this->isQuietModeActive)
                                @php
                                    $friendlyDate = Auth::user()->quiet_until->format('l, F j');
                                    $daysLeft = $this->daysLeft;
                                @endphp

                                @if ($daysLeft > 1)
                                    {{ __('Active for :count more days (until :date)', ['count' => $daysLeft, 'date' => $friendlyDate]) }}
                                @elseif ($daysLeft == 1)
                                    {{ __('Active for 1 more day (until :date)', ['date' => $friendlyDate]) }}
                                @else
                                    {{ __('Ending today (:date)', ['date' => $friendlyDate]) }}
                                @endif
                            @else
                                {{ __('Currently not using quiet mode.') }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
                    @if (! $this->isQuietModeActive)
                        <form wire:submit.prevent="enableQuietMode">
                            <div class="mb-4">
                                <x-input-label for="quietUntilDate" :value="__('Enable Quiet Mode Until')" />
                                <x-text-input
                                    name="quietUntilDate"
                                    wire:model="quietUntilDate"
                                    id="quietUntilDate"
                                    type="date"
                                    class="mt-1 block w-full"
                                    min="{{ now()->addDay()->format('Y-m-d') }}"
                                    required
                                />
                                <x-input-error :messages="$errors->get('quietUntilDate')" class="mt-2" />
                            </div>
                            <div class="mb-4 mt-4">
                                <x-input-label>
                                    {{ __('Quick Duration Options') }}
                                </x-input-label>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Click a duration to pre-fill the input above.') }}
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($suggestedDates as $days => $label)
                                        <button
                                            type="button"
                                            wire:click="selectSuggestedDate({{ $days }})"
                                            class="inline-flex items-center rounded-full border border-gray-300 bg-white px-3 py-1 text-xs font-medium text-gray-700 shadow-sm transition-all duration-200 ease-in-out hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                        >
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <x-primary-button type="submit" class="mt-4 w-full justify-center">
                                {{ __('Enable Quiet Mode') }}
                            </x-primary-button>
                        </form>
                    @else
                        <x-danger-button wire:click="disableQuietMode" class="w-full justify-center">
                            {{ __('Disable Quiet Mode') }}
                        </x-danger-button>
                    @endif
                </div>

                <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between text-left text-gray-900 dark:text-gray-100"
                        wire:click="toggleFaq"
                    >
                        <span class="text-lg font-semibold">
                            {{ __('Frequently Asked Questions') }}
                        </span>
                        <span class="ml-6 flex-shrink-0">
                            @if ($isFaqOpen)
                                @svg('hugeicons-arrow-up-01', 'h-6 w-6')
                            @else
                                @svg('hugeicons-arrow-down-01', 'h-6 w-6')
                            @endif
                        </span>
                    </button>
                    <div
                        x-show="$wire.isFaqOpen"
                        x-transition:enter="transition duration-200 ease-out"
                        x-transition:enter-start="-translate-y-2 transform opacity-0"
                        x-transition:enter-end="translate-y-0 transform opacity-100"
                        x-transition:leave="transition duration-200 ease-in"
                        x-transition:leave-start="translate-y-0 transform opacity-100"
                        x-transition:leave-end="-translate-y-2 transform opacity-0"
                        class="mt-4 space-y-4"
                    >
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                            <h5 class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('What happens to my notifications during Quiet Mode?') }}
                            </h5>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                {{ __('During Quiet Mode, all but the most critical notifications about your backup tasks are paused. They are automatically un-paused when you leave Quiet Mode.') }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                            <h5 class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Can I end Quiet Mode early?') }}
                            </h5>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                {{ __('Yes, you can disable Quiet Mode at any time by clicking the "Disable Quiet Mode" button. You\'ll immediately start receiving notifications again.') }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                            <h5 class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Will I receive any notifications during Quiet Mode?') }}
                            </h5>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                {{ __('During Quiet Mode, you\'ll receive two types of important notifications:') }}
                            </p>
                            <ul class="mt-2 list-inside list-disc text-sm text-gray-600 dark:text-gray-300">
                                <li>
                                    {{ __('An email alert when your Quiet Mode period is about to end.') }}
                                </li>
                                <li>
                                    {{ __('Any critical backup failure emails. These are not additional notifications, but rather essential alerts about the status of your backups.') }}
                                </li>
                            </ul>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                {{ __('All other notifications will not be sent.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-form-wrapper>
</div>
