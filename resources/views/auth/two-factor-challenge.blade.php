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
                    Two-Factor Authentication
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

                <div class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-center mb-3">
                        <p><span class="font-semibold">Welcome back, {{ Auth::user()->first_name }}!</span> To ensure the security of your account, please enter your two-factor authentication code.</p>
                    </div>
                    <div class="flex items-center">
                        <p>If you're unable to access your two-factor device, you can use one of your recovery codes instead.</p>
                    </div>
                </div>

                <form class="space-y-6" action="{{ route('two-factor.challenge') }}" method="POST" id="twoFactorForm">
                    @csrf
                    <input type="hidden" id="hiddenCode" name="code" value="" />

                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Using recovery code?</span>
                        <label class="switch">
                            <input type="checkbox" id="codeTypeToggle">
                            <span class="slider round"></span>
                        </label>
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
                <a href="https://github.com/vanguardbackup/vanguard" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300" target="_blank">
                    <span class="sr-only">GitHub</span>
                    <svg height="32" aria-hidden="true" viewBox="0 0 16 16" version="1.1" width="32" data-view-component="true" class="octicon octicon-mark-github v-align-middle">
                        <path fill-rule="evenodd" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path>
                    </svg>
                </a>
            </div>
        </footer>
    </div>

    <style>
        /* Toggle switch styles */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }
        input:checked + .slider {
            background-color: #000000;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .slider.round {
            border-radius: 34px;
        }
        .slider.round:before {
            border-radius: 50%;
        }
    </style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const codeInput = document.getElementById('code');
        const recoveryCodeInput = document.getElementById('recoveryCodeInput').querySelector('input');
        const hiddenCodeInput = document.getElementById('hiddenCode');
        const form = document.getElementById('twoFactorForm');
        const codeTypeToggle = document.getElementById('codeTypeToggle');
        const authCodeInputDiv = document.getElementById('authCodeInput');
        const recoveryCodeInputDiv = document.getElementById('recoveryCodeInput');
        const codeError = document.getElementById('codeError');
        const recoveryCodeError = document.getElementById('recoveryCodeError');
        const submitButton = document.querySelector('button[type="submit"]');

        function updateHiddenInput(value) {
            hiddenCodeInput.value = value;
        }

        function validateInput(input, errorElement) {
            if (!input.value) {
                errorElement.textContent = 'This field is required.';
                errorElement.style.display = 'block';
                return false;
            }
            errorElement.style.display = 'none';
            return true;
        }

        codeInput.addEventListener('input', function () {
            updateHiddenInput(this.value);
            validateInput(this, codeError);
            if (this.value.length === 6) {
                submitForm();
            }
        });

        recoveryCodeInput.addEventListener('input', function () {
            updateHiddenInput(this.value);
            validateInput(this, recoveryCodeError);
        });

        codeTypeToggle.addEventListener('change', function () {
            if (this.checked) {
                authCodeInputDiv.style.display = 'none';
                recoveryCodeInputDiv.style.display = 'block';
                recoveryCodeInput.focus();
            } else {
                authCodeInputDiv.style.display = 'block';
                recoveryCodeInputDiv.style.display = 'none';
                codeInput.focus();
            }
            hiddenCodeInput.value = '';
            codeError.style.display = 'none';
            recoveryCodeError.style.display = 'none';
        });

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
            if (codeTypeToggle.checked) {
                isValid = validateInput(recoveryCodeInput, recoveryCodeError);
            } else {
                isValid = validateInput(codeInput, codeError);
            }
            if (isValid) {
                form.submit();
            }
        }
    });
</script>
</x-minimal-layout>
