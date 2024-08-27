@php
    $currentRoute = Route::currentRouteName();
    $quietUntil = Auth::user()->quiet_until;
    $isOnQuietModePage = $currentRoute === 'profile.quiet-mode';
    $isQuietModeActive = $quietUntil && $quietUntil->isFuture();
    $daysLeft = $isQuietModeActive ? floor(now()->floatDiffInDays($quietUntil)) : 0;
@endphp

@if ($isQuietModeActive)
    <div class="rounded-none bg-blue-50 p-4 shadow-none dark:bg-blue-900/50">
        <div class="mx-auto max-w-6xl">
            <div class="flex items-start">
                <div class="mr-4 flex-shrink-0 rounded-full bg-blue-100 p-2 dark:bg-blue-800">
                    @svg('hugeicons-notification-snooze-02', 'h-6 w-6 text-blue-500 dark:text-blue-300')
                </div>
                <div class="flex-grow">
                    <div class="mb-2 flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200">
                            {{ __('Quiet Mode Active') }}
                        </h4>
                        <span
                            class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-600 dark:bg-blue-800 dark:text-blue-400"
                        >
                            @if ($daysLeft > 1)
                                {{ __(':count days left', ['count' => $daysLeft]) }}
                            @elseif ($daysLeft == 1)
                                {{ __('1 day left') }}
                            @else
                                {{ __('Ending today') }}
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-blue-600 dark:text-blue-300">
                            {{ __('Notifications are paused until :endDate.', ['endDate' => $quietUntil->format('F j, Y')]) }}
                        </p>
                        @unless ($isOnQuietModePage)
                            <a
                                href="{{ route('profile.quiet-mode') }}"
                                wire:navigate
                                class="ml-4 inline-flex items-center text-sm font-medium text-blue-600 transition duration-150 ease-in-out hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
                            >
                                {{ __('Manage') }}
                                @svg('hugeicons-arrow-right-double', 'ml-1 h-4 w-4')
                            </a>
                        @endunless
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
