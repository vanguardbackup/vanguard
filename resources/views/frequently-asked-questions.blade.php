@section('title', 'Frequently Asked Questions')
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Frequently Asked Questions') }}
        </h2>
    </x-slot>
    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <x-form-wrapper>
                <div class="p-6 sm:px-20">
                    <div class="text-2xl dark:text-gray-200 font-semibold">
                        Frequently Asked Questions
                    </div>
                    <div class="mt-6 text-gray-500 dark:text-gray-300">
                        <div class="mb-4">
                            <div class="font-bold text-gray-700">Why do I need to provide my database password?</div>
                            <div class="mt-2">A: We need your database password to connect to your database and perform
                                the backup operation. It is stored securely in our database.
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="font-bold text-gray-700">Vanguard isn't able to connect to my remote server,
                                why?
                            </div>
                            <div class="mt-2">Make sure you have provided the correct SSH credentials. Also, make sure
                                that the SSH port is open on your server. If you are using a custom port, make sure to
                                provide the correct port number in the SSH port field.
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="font-bold text-gray-700">My backup task is stuck at running?</div>
                            <div class="mt-2">Sometimes your backup task may get stuck at running. This can happen
                                if
                                the task is taking longer than expected to complete. If your task is stuck at
                                running for 30 minutes it will automatically cancel.
                            </div>
                        </div>
                    </div>
                </div>
            </x-form-wrapper>
        </div>
    </div>
</x-app-layout>
