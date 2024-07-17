<div>
    @section('title', __('Notification Streams'))
    <x-slot name="header">
        {{ __('Notification Streams') }}
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (!Auth::user()->notificationStreams->isEmpty())
                <div class="mb-4 sm:mb-6 flex justify-center sm:justify-end">
                    <a href="{{ route('notification-streams.create') }}" wire:navigate class="w-full sm:w-auto">
                        <x-primary-button class="w-full sm:w-auto justify-center">
                            {{ __('Add Notification Stream') }}
                        </x-primary-button>
                    </a>
                </div>
            @endif

            @livewire('notification-streams.tables.index-table')
        </div>
    </div>
</div>
