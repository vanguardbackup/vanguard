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
                    autofocus
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
        @if ($totalMatches > 0)
            <div class="my-5">
                <x-form-wrapper>
                    <x-slot name="title">{{ __('IP Match Found') }}</x-slot>
                    <x-slot name="description">
                        {{ __('Found :count user(s) associated with the IP Address ":ip".', ['count' => $totalMatches, 'ip' => $ipAddress]) }}
                    </x-slot>
                    <x-slot name="icon">hugeicons-tick-01</x-slot>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                                    >
                                        {{ __('Name') }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                                    >
                                        {{ __('Registration IP') }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                                    >
                                        {{ __('Login IP') }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                                    >
                                        {{ __('Joined') }}
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                                    >
                                        {{ __('Last Login') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @foreach ($results as $user)
                                    <tr>
                                        <td class="flex items-center whitespace-nowrap px-6 py-4">
                                            <img
                                                src="{{ $user['gravatar'] }}"
                                                alt="{{ $user['name'] }}"
                                                class="mr-3 h-10 w-10 rounded-full"
                                            />
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $user['name'] }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $user['email'] }}
                                                </div>
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
                </x-form-wrapper>
            </div>
        @else
            <div class="my-5">
                <x-no-content withBackground>
                    <x-slot name="icon">
                        @svg('hugeicons-cancel-01', 'inline h-16 w-16 text-primary-900 dark:text-white')
                    </x-slot>
                    <x-slot name="title">
                        {{ __('No Matches Found') }}
                    </x-slot>
                    <x-slot name="description">
                        {{ __('Unable to find information for ":ip" in the database.', ['ip' => $ipAddress]) }}
                    </x-slot>
                </x-no-content>
            </div>
        @endif
    @endif
</div>
