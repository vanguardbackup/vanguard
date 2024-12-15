<div>
    @section('title', __('Year in Review'))
    <x-slot name="header">
        {{ __('Year in Review') }}
    </x-slot>
    <x-form-wrapper>
        <x-slot name="icon">hugeicons-cheese-cake-01</x-slot>
        <x-slot name="title">
            <span class="font-bold text-indigo-700 dark:text-indigo-400">{{ __('Year in Review for ') }}</span>
            {{ date('Y') }}
        </x-slot>
        @if (! $hasYearInReviewData)
            <div>
                <div class="my-6 text-center">
                    <img
                        class="shimmer-border mx-auto h-32 w-32 rounded-full object-cover"
                        src="{{ Auth::user()->gravatar('160') }}"
                        alt="{{ __('User Avatar') }}"
                    />
                </div>
                <section class="rounded-lg bg-white p-6 text-center shadow-lg dark:bg-gray-900">
                    <p class="my-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ __('Hey, ') }} {{ Auth::user()->first_name }}! 👋
                    </p>
                    <p class="text-gray-600 dark:text-gray-300">
                        {{ __('Welcome to your Year in Review of using ') }}
                        <strong>{{ config('app.name') }}</strong>
                        .
                        <br />
                        <strong>{{ __('Ready to get started?') }}</strong>
                    </p>
                    <p class="mt-4 text-gray-500 dark:text-gray-400">
                        {{ __('Click the button below, and we\'ll fetch your backup statistics for ') }}
                        {{ date('Y') }}.
                    </p>
                    <div class="mt-6">
                        <x-primary-button centered wire:click="generateYearInReviewData">
                            {{ __('Let\'s go! 🚀') }}
                        </x-primary-button>
                    </div>
                </section>
            </div>
        @elseif ($currentlyGeneratingYearInReview)
            <div class="space-y-4 text-center">
                <div class="my-3">
                    <x-spinner class="inline h-16 text-indigo-600 dark:text-indigo-400" />
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ __('Stand by!') }}
                </h1>
                <p class="text-gray-600 dark:text-gray-300">
                    {{ __('We are currently fetching your backup details for this year. This might take a few seconds!') }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('If this page does not refresh automatically, please try reloading the page manually.') }}
                </p>
            </div>
        @else
            <div>
                <p class="my-3 text-gray-600 dark:text-gray-300">
                    {{ __('The following information is curated from your activity on :app within the current year.', ['app' => config('app.name')]) }}
                </p>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div
                        class="rounded-lg bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 p-6 text-white shadow-lg"
                    >
                        <h1 class="text-lg font-bold">
                            {{ __('Backup Tasks Created') }}
                        </h1>
                        <p class="mt-2 text-4xl font-bold">
                            {{ number_format($yearInReviewData['backup_tasks_created']) }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg bg-gradient-to-r from-green-500 via-teal-500 to-blue-500 p-6 text-white shadow-lg"
                    >
                        <h1 class="text-lg font-bold">
                            {{ __('Backup Tasks Ran') }}
                        </h1>
                        <p class="mt-2 text-4xl font-bold">
                            {{ number_format($yearInReviewData['backup_tasks_ran']) }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg bg-gradient-to-r from-yellow-500 via-orange-500 to-red-500 p-6 text-white shadow-lg"
                    >
                        <h1 class="text-lg font-bold">
                            {{ __('Successful Backups') }}
                        </h1>
                        <p class="mt-2 text-4xl font-bold">
                            {{ number_format($yearInReviewData['successful_backup_tasks']) }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg bg-gradient-to-r from-cyan-500 via-blue-500 to-indigo-500 p-6 text-white shadow-lg"
                    >
                        <h1 class="text-lg font-bold">
                            {{ __('Total Data Backed Up') }}
                        </h1>
                        <p class="mt-2 text-4xl font-bold">
                            {{ $yearInReviewData['data_amount'] }}
                        </p>
                    </div>
                </div>
                <p class="mt-8 text-center text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Thanks for participating in our Year in Review! We hope you enjoy using :app in :new_year.', ['new_year' => \Carbon\Carbon::now()->addYear()->year, 'app' => config('app.name')]) }}
                </p>
            </div>
        @endif
    </x-form-wrapper>
    <style>
        /* Add the shimmer border animation */
        .shimmer-border {
            position: relative;
            border-radius: 50%;
            border: 5px solid transparent;
            background-image: linear-gradient(
                90deg,
                rgba(102, 126, 234, 0.7),
                rgba(226, 232, 240, 0.7),
                rgba(147, 51, 234, 0.7),
                rgba(56, 189, 248, 0.7),
                rgba(249, 115, 22, 0.7),
                rgba(248, 113, 113, 0.7)
            );
            background-size: 500% 500%;
            animation: shimmer 4s infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }
    </style>
</div>
