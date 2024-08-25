@php
    $currentRoute = Route::currentRouteName();
    $quietUntil = Auth::user()->quiet_until;
    $isOnQuietModePage = $currentRoute === 'profile.quiet-mode';
    $isQuietModeActive = $quietUntil && $quietUntil->isFuture();
    $daysLeft = $isQuietModeActive ? floor(now()->floatDiffInDays($quietUntil)) : 0;
@endphp

@if ($isQuietModeActive)
    <div class="bg-blue-50 dark:bg-blue-900/50 rounded-none p-4 shadow-none">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-start">
                <div class="flex-shrink-0 bg-blue-100 dark:bg-blue-800 rounded-full p-2 mr-4">
                    @svg('hugeicons-notification-snooze-02', 'w-6 h-6 text-blue-500 dark:text-blue-300')
                </div>
                <div class="flex-grow">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200">
                            {{ __('Quiet Mode Active') }}
                        </h4>
                        <span class="text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-800 px-2 py-1 rounded-full">
                        @if ($daysLeft > 1)
                                {{ __(':count days left', ['count' => $daysLeft]) }}
                            @elseif ($daysLeft == 1)
                                {{ __('1 day left') }}
                            @else
                                {{ __('Ending today') }}
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-blue-600 dark:text-blue-300">
                            {{ __('Notifications are paused until :endDate.', ['endDate' => $quietUntil->format('F j, Y')]) }}
                        </p>
                        @unless ($isOnQuietModePage)
                            <a href="{{ route('profile.quiet-mode') }}" wire:navigate class="inline-flex items-center ml-4 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 transition ease-in-out duration-150">
                                {{ __('Manage') }}
                                @svg('hugeicons-arrow-right-double', 'w-4 h-4 ml-1')
                            </a>
                        @endunless
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
