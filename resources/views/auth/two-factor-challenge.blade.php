<x-minimal-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="flex items-center justify-between">
                <x-application-logo class="h-11 w-auto fill-current text-primary-950 dark:text-white" />
                <div class="flex items-center space-x-4">
                    <img
                        class="h-8 w-8 rounded-full border border-gray-300 dark:border-gray-700"
                        src="{{ Auth::user()->gravatar(60) }}"
                        alt="{{ Auth::user()->first_name }}"
                    />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ Auth::user()->first_name }}
                    </span>
                </div>
            </div>
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                    Two-factor authentication
                </h2>
            </div>
            <div class="mt-8 bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-[0.70rem] sm:px-10">
                @if ($errors->any())
                    <div class="rounded-md bg-red-50 dark:bg-red-900 p-4 mb-4">
                        <div class="flex">
                            <div class="text-sm text-red-700 dark:text-red-200">
                                {{ $errors->first('code') }}
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-center mb-2">
                        @svg('heroicon-o-shield-check', 'h-5 w-5 text-gray-400 mr-2')
                        <p>Hey {{ Auth::user()->first_name }}, please enter your two-factor authentication code to continue.</p>
                    </div>
                    <div class="flex items-center">
                        @svg('heroicon-o-key', 'h-5 w-5 text-gray-400 mr-2')
                        <p>If you've lost access to your two-factor device, you can use one of your recovery codes.</p>
                    </div>
                </div>

                <form class="space-y-6" action="{{ route('two-factor.challenge') }}" method="POST" id="twoFactorForm">
                    @csrf
                    <input type="hidden" id="hiddenCode" name="code" value="" />
                    <div>
                        <x-input-label for="code" value="Authentication code" />
                        <x-text-input
                            id="code"
                            name="code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            required
                            autofocus
                            maxlength="6"
                            class="mt-1 block w-full"
                            placeholder="Enter your 6-digit code"
                        />
                    </div>
                    <div class="flex flex-col sm:flex-row sm:space-x-5 space-y-4 sm:space-y-0">
                        <div class="w-full sm:w-4/6">
                            <x-primary-button type="submit" class="w-full justify-center" centered action="submit" noLivewire>
                                @svg('heroicon-o-finger-print', 'w-5 h-5 mr-2')
                                {{ __('Verify') }}
                            </x-primary-button>
                        </div>
                        <div class="w-full sm:w-2/6">
                            <a href="{{ route('login') }}" class="block">
                                <x-secondary-button type="button" class="w-full justify-center" centered>
                                    {{ __('Log Out') }}
                                </x-secondary-button>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <footer class="mt-8 text-center">
            <div class="mt-2 flex justify-center space-x-6">
                <a href="https://github.com/vanguardbackup/vanguard" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300" target="_blank">
                    <span class="sr-only">GitHub</span>
                    @svg('heroicon-o-code-bracket', 'h-6 w-6')
                </a>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const codeInput = document.getElementById('code');
            const hiddenCodeInput = document.getElementById('hiddenCode');
            const form = document.getElementById('twoFactorForm');

            codeInput.addEventListener('input', function () {
                hiddenCodeInput.value = this.value; // Update hidden input with the code value
                if (this.value.length === 6) {
                    form.requestSubmit(); // Submit the form when 6 digits are entered
                }
            });
        });
    </script>
</x-minimal-layout>
