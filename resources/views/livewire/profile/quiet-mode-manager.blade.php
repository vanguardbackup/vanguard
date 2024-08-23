<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

new class extends Component {
    public ?string $quietUntilDate = null;

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
        return $this->quietUntilDate !== null && Carbon::parse($this->quietUntilDate)->isFuture();
    }

    #[Computed]
    public function daysLeft(): int
    {
        if (!$this->isQuietModeActive) {
            return 0;
        }
        return floor(now()->floatDiffInDays(Carbon::parse($this->quietUntilDate)));
    }

    public function enableQuietMode(): void
    {
        $this->validate([
            'quietUntilDate' => 'required|date|after:today',
        ], [
            'quietUntilDate.required' => __('Please specify the duration for which notifications should be silenced.')
        ]);

        $user = Auth::user();
        $user->quiet_until = Carbon::parse($this->quietUntilDate)->endOfDay();
        $user->save();

        $this->refreshQuietUntilDate();

        $friendlyDate = Carbon::parse($this->quietUntilDate)->format('l, F j');
        Toaster::success(__("Shh... Quiet Mode activated! You'll have peace until :date.", ['date' => $friendlyDate]));
    }

    public function disableQuietMode(): void
    {
        $user = Auth::user();
        $user->quiet_until = null;
        $user->save();

        $this->quietUntilDate = null;
        Toaster::success(__("Welcome back! Quiet Mode is now off."));
    }
}

?>

<div>
    <x-form-wrapper>
        <x-slot name="title">{{ __('Manage Quiet Mode') }}</x-slot>
        <x-slot name="description">
            {{ __('Manage your Quiet Mode settings to temporarily pause notifications.') }}
        </x-slot>
        <x-slot name="icon">heroicon-o-bell-snooze</x-slot>

        <div class="space-y-6">
            <div class="rounded-lg p-6">
                <div class="flex items-center mb-4">
                    @svg('heroicon-o-bell-snooze', 'w-10 h-10 text-gray-500 dark:text-gray-400 mr-4')
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Quiet Mode Status') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            @if ($this->isQuietModeActive)
                                @php
                                    $friendlyDate = Carbon::parse($quietUntilDate)->format('l, F j');
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
                                {{ __('Currently inactive') }}
                            @endif
                        </p>
                    </div>
                </div>

                @if ($this->isQuietModeActive)
                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/50 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                @svg('heroicon-s-envelope', 'h-5 w-5 text-blue-400')
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    {{ __("You'll receive an email when your Quiet Mode period ends.") }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mt-6">
                    @if (!$this->isQuietModeActive)
                        <div class="mb-4">
                            <x-input-label for="quietUntilDate" :value="__('Enable Quiet Mode Until')" />
                            <x-text-input
                                name="quietUntilDate"
                                wire:model="quietUntilDate"
                                id="quietUntilDate"
                                type="date"
                                class="mt-1 block w-full"
                                min="{{ now()->addDay()->format('Y-m-d') }}"
                            />
                            <x-input-error :messages="$errors->get('quietUntilDate')" class="mt-2" />
                        </div>
                        <x-primary-button wire:click="enableQuietMode" class="w-full justify-center">
                            {{ __('Enable Quiet Mode') }}
                        </x-primary-button>
                    @else
                        <x-danger-button wire:click="disableQuietMode" class="w-full justify-center">
                            {{ __('Disable Quiet Mode') }}
                        </x-danger-button>
                    @endif
                </div>
            </div>
        </div>
    </x-form-wrapper>
</div>
