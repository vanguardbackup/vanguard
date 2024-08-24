<template x-if="!$wire.form.value">
    <div class="mt-4" x-data="telegram">
        <x-primary-button type="button" centered x-on:click="loadData">{{ __('Get my Telegram ID') }}</x-primary-button>
    </div>
</template>

@assets
    <script async src="https://telegram.org/js/telegram-widget.js?2" defer></script>
@endassets

@script
    <script>
        Alpine.data('telegram', () => {
            return {
                loadData() {
                    //if Telegram script was loaded successfully
                    if (window.Telegram) {
                        try {
                            //try to authenticate Telegram user
                            window.Telegram.Login.auth({
                                    bot_id: "{{ config('services.telegram.bot_id') }}",
                                    request_access: true
                                },
                                (data) => {
                                    //if authentication failed, warn user and log the event at the backend side
                                    if (!data) {
                                        Toaster.error("{{ __('Authorization failed! Please try again.') }}");
                                        $wire.dispatchSelf('jsError', {
                                            message: 'Authorization failed on Telegram side'
                                        });
                                    //if authentication succeeded, update the form value
                                    } else {
                                        $wire.form.value = data.id;
                                    }
                                }
                            );
                        //if there are problems with our request, warn user and log the event at the backend side
                        } catch (error) {
                            Toaster.error("{{ __('Something went wrong! Please try again later.') }}");
                            $wire.dispatchSelf('jsError', {
                                message: error.message
                            });
                        }
                    //if Telegram script was not loaded, warn user and log the event at the backend side
                    } else {
                        Toaster.error("{{ __('Something went wrong! Please reload the page and try again.') }}");
                        $wire.dispatch('jsError', {
                            message: 'There is no Telegram object when trying to authenticate Telegram user.'
                        });
                    }
                }
            }
        })
    </script>
@endscript
