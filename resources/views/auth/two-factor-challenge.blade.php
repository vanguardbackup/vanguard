<x-minimal-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <div class="flex flex-col sm:flex-row items-center justify-between">
                <x-application-logo class="h-11 w-auto fill-current text-primary-950 dark:text-white" />
                <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4 mt-4 sm:mt-0">
                    <img
                        class="h-12 w-12 rounded-full border-2 border-gray-300 dark:border-gray-700"
                        src="{{ Auth::user()->gravatar(60) }}"
                        alt="{{ Auth::user()->first_name }}"
                    />
                    <span class="text-base font-medium text-gray-700 dark:text-gray-300">
                        {{ Auth::user()->first_name }}
                    </span>
                </div>
            </div>
            <div>
                <h2 class="mt-6 text-center text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white">
                    Two-Factor Authentication
                </h2>
            </div>
            <div class="mt-8 bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
                @if ($errors->any())
                    <div class="rounded-md bg-red-50 dark:bg-red-900 p-4 mb-4">
                        <div class="flex">
                            <div class="text-sm text-red-700 dark:text-red-200">
                                {{ $errors->first('code') }}
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                    <p class="mb-3"><span class="font-semibold">Welcome back, {{ Auth::user()->first_name }}!</span> Please enter your two-factor authentication code.</p>
                    <p>If you can't access your two-factor device, use a recovery code.</p>
                </div>

                <form class="space-y-6" action="{{ route('two-factor.challenge') }}" method="POST" id="twoFactorForm">
                    @csrf
                    <input type="hidden" id="hiddenCode" name="code" value="" />

                    <div class="flex justify-center mb-4">
                        <div class="inline-flex rounded-md shadow-sm" role="group" aria-label="Authentication type">
                            <button type="button" id="authCodeBtn" class="flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-gray-950 focus:z-10 focus:ring-2 focus:ring-gray-950 focus:text-gray-950 dark:bg-gray-800 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-500 dark:focus:text-white">
                                @svg('heroicon-o-key', 'w-5 h-5 mr-2')
                                Auth Code
                            </button>
                            <button type="button" id="recoveryCodeBtn" class="flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-lg hover:bg-gray-100 hover:text-gray-950 focus:z-10 focus:ring-2 focus:ring-gray-950 focus:text-gray-950 dark:bg-gray-800 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-500 dark:focus:text-white">
                                @svg('heroicon-o-document-text', 'w-5 h-5 mr-2')
                                Recovery Code
                            </button>
                        </div>
                    </div>

                    <div id="authCodeInput">
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
                        <div id="codeError" class="text-red-600 text-sm mt-1" style="display: none;"></div>
                    </div>

                    <div id="recoveryCodeInput" style="display: none;">
                        <x-input-label for="recoveryCode" value="Recovery code" />
                        <x-text-input
                            id="recoveryCode"
                            name="recoveryCode"
                            type="text"
                            autocomplete="off"
                            required
                            class="mt-1 block w-full"
                            placeholder="Enter your recovery code"
                        />
                        <div id="recoveryCodeError" class="text-red-600 text-sm mt-1" style="display: none;"></div>
                    </div>

                    <div>
                        <x-primary-button type="submit" class="w-full justify-center" centered fat noLivewire>
                            @svg('heroicon-o-finger-print', 'w-5 h-5 mr-2')
                            {{ __('Verify and Proceed') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
        <footer class="mt-8 text-center">
            <div class="mt-2 flex justify-center space-x-6">
                <a href="https://github.com/vanguardbackup/vanguard" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300" target="_blank" rel="noopener noreferrer">
                    <span class="sr-only">GitHub</span>
                    <svg height="32" aria-hidden="true" viewBox="0 0 16 16" version="1.1" width="32" data-view-component="true" class="octicon octicon-mark-github v-align-middle">
                        <path fill-rule="evenodd" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path>
                    </svg>
                </a>
            </div>
        </footer>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const codeInput = document.getElementById('code');
        const recoveryCodeInput = document.getElementById('recoveryCodeInput').querySelector('input');
        const hiddenCodeInput = document.getElementById('hiddenCode');
        const form = document.getElementById('twoFactorForm');
        const authCodeBtn = document.getElementById('authCodeBtn');
        const recoveryCodeBtn = document.getElementById('recoveryCodeBtn');
        const authCodeInputDiv = document.getElementById('authCodeInput');
        const recoveryCodeInputDiv = document.getElementById('recoveryCodeInput');
        const codeError = document.getElementById('codeError');
        const recoveryCodeError = document.getElementById('recoveryCodeError');
        const submitButton = document.querySelector('button[type="submit"]');

        function updateHiddenInput(value) {
            hiddenCodeInput.value = value;
        }

        function validateInput(input, errorElement) {
            if (!input.value.trim()) {
                errorElement.textContent = 'Please enter your code.';
                errorElement.style.display = 'block';
                return false;
            }
            errorElement.style.display = 'none';
            return true;
        }

        function handleInputChange(input, errorElement) {
            updateHiddenInput(input.value);
            validateInput(input, errorElement);
            if (input === codeInput && input.value.length === 6) {
                submitForm();
            }
        }

        codeInput.addEventListener('input', () => handleInputChange(codeInput, codeError));
        recoveryCodeInput.addEventListener('input', () => handleInputChange(recoveryCodeInput, recoveryCodeError));

        function toggleInputs(showAuth) {
            authCodeInputDiv.style.display = showAuth ? 'block' : 'none';
            recoveryCodeInputDiv.style.display = showAuth ? 'none' : 'block';
            authCodeBtn.classList.toggle('bg-blue-500', showAuth);
            recoveryCodeBtn.classList.toggle('bg-blue-500', !showAuth);

            const activeInput = showAuth ? codeInput : recoveryCodeInput;
            activeInput.value = '';
            hiddenCodeInput.value = '';
            codeError.style.display = 'none';
            recoveryCodeError.style.display = 'none';

            // Ensure focus is set after a short delay
            setTimeout(() => {
                activeInput.focus();
            }, 0);
        }

        authCodeBtn.addEventListener('click', () => toggleInputs(true));
        recoveryCodeBtn.addEventListener('click', () => toggleInputs(false));

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submitForm();
        });

        submitButton.addEventListener('click', function (event) {
            event.preventDefault();
            submitForm();
        });

        function submitForm() {
            let isValid = true;
            const activeInput = recoveryCodeInputDiv.style.display !== 'none' ? recoveryCodeInput : codeInput;
            const activeError = recoveryCodeInputDiv.style.display !== 'none' ? recoveryCodeError : codeError;

            isValid = validateInput(activeInput, activeError);

            if (isValid) {
                if (!activeInput.value.trim()) {
                    activeError.textContent = 'Please enter your code.';
                    activeError.style.display = 'block';
                    activeInput.focus();
                    return;
                }
                form.submit();
            } else {
                activeInput.focus();
            }
        }

        // Ensure initial focus
        codeInput.focus();

        // Handle tab key to prevent losing focus
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                const focusableElements = form.querySelectorAll('input, button');
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];

                if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                } else if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            }
        });
    });
</script>
</x-minimal-layout>
