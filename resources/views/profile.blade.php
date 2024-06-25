<x-account-wrapper pageTitle="{{ __('Profile') }}">
    <div>
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow-none sm:rounded-[0.70rem] border border-gray-950/5">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form/>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow-none sm:rounded-[0.70rem] border border-gray-950/5">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form/>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow-none sm:rounded-[0.70rem] border border-gray-950/5">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form/>
                </div>
            </div>
        </div>
    </div>
</x-account-wrapper>
