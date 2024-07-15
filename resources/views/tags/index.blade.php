<x-account-wrapper pageTitle="{{ __('Tags') }}">
    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (!Auth::user()->tags->isEmpty())
                <div class="mb-4 sm:mb-6 flex justify-center sm:justify-end">
                    <a href="{{ route('tags.create') }}" wire:navigate class="w-full sm:w-auto">
                        <x-primary-button class="w-full sm:w-auto justify-center">
                            {{ __('Create Tag') }}
                        </x-primary-button>
                    </a>
                </div>
            @endif
            @livewire('tags.index-table')
        </div>
    </div>
</x-account-wrapper>
