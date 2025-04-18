<div>
    @section('title', __('IP Checker'))
    <x-slot name="header">
        {{ __('IP Checker') }}
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <x-form-wrapper>
            <x-slot name="title">{{ __('IP Checker') }}</x-slot>
            <x-slot name="description">
                {{ __('Quickly check an IP Address against users in the system.') }}
            </x-slot>
            <x-slot name="icon">hugeicons-internet-antenna-02</x-slot>
            <form wire:submit.prevent="check">
                <div class="mb-4">
                    <x-input-label for="ipAddress" :value="__('IP Address')" />
                    <x-text-input
                        name="ipAddress"
                        wire:model="ipAddress"
                        id="ipAddress"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="192.168.1.1"
                    />
                    <x-input-error :messages="$errors->get('ipAddress')" class="mt-2" />
                </div>

                <div class="mb-4">
                    <x-input-label :value="__('Search In')" class="mb-2" />
                    <div class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            wire:click="updateSearchType('both')"
                            class="{{ $searchType === 'both' ? 'bg-primary-500 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }} rounded-md px-4 py-2 text-sm"
                        >
                            {{ __('Both') }}
                        </button>
                        <button
                            type="button"
                            wire:click="updateSearchType('registration')"
                            class="{{ $searchType === 'registration' ? 'bg-primary-500 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }} rounded-md px-4 py-2 text-sm"
                        >
                            {{ __('Registration IP') }}
                        </button>
                        <button
                            type="button"
                            wire:click="updateSearchType('login')"
                            class="{{ $searchType === 'login' ? 'bg-primary-500 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }} rounded-md px-4 py-2 text-sm"
                        >
                            {{ __('Login IP') }}
                        </button>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700"></div>

                <div class="flex gap-4">
                    <x-primary-button type="submit" class="mt-4 w-full justify-center">
                        {{ __('Check IP Address') }}
                    </x-primary-button>

                    @if ($checked)
                        <x-secondary-button wire:click="clear" type="button" class="mt-4 w-full justify-center">
                            {{ __('Clear Results') }}
                        </x-secondary-button>
                    @endif
                </div>
            </form>
        </x-form-wrapper>

        @if ($checked)
            <div class="mt-8 overflow-hidden bg-white shadow sm:rounded-lg dark:bg-gray-800">
                <div class="p-6">
                    <div class="mb-4 flex items-center">
                        @if ($totalMatches > 0)
                            <div
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900"
                            >
                                <svg
                                    class="h-6 w-6 text-green-600 dark:text-green-300"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ __('IP Match Found') }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Found :count user(s) associated with this IP address.', ['count' => $totalMatches]) }}
                                </p>
                            </div>
                        @else
                            <div
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900"
                            >
                                <svg
                                    class="h-6 w-6 text-red-600 dark:text-red-300"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ __('No Matches Found') }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('There appears to be no history of this IP for the users in our system.') }}
                                </p>
                            </div>
                        @endif
                    </div>

                    @if ($totalMatches > 0)
                        <div class="mt-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                                            >
                                                {{ __('User') }}
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                                            >
                                                {{ __('Registration') }}
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                                            >
                                                {{ __('Login') }}
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                                            >
                                                {{ __('Created') }}
                                            </th>
                                            <th
                                                scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                                            >
                                                {{ __('Last Login') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800"
                                    >
                                        @foreach ($results as $user)
                                            <tr>
                                                <td class="whitespace-nowrap px-6 py-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $user['name'] }}
                                                    </div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $user['email'] }}
                                                    </div>
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4">
                                                    @if ($user['registration_match'])
                                                        <span
                                                            class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-900 dark:text-green-200"
                                                        >
                                                            {{ __('Match') }}
                                                        </span>
                                                    @else
                                                        <span
                                                            class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                                                        >
                                                            {{ __('No Match') }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4">
                                                    @if ($user['login_match'])
                                                        <span
                                                            class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-900 dark:text-green-200"
                                                        >
                                                            {{ __('Match') }}
                                                        </span>
                                                    @else
                                                        <span
                                                            class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                                                        >
                                                            {{ __('No Match') }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td
                                                    class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400"
                                                >
                                                    {{ $user['created_at'] }}
                                                </td>
                                                <td
                                                    class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400"
                                                >
                                                    {{ $user['last_login'] }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
