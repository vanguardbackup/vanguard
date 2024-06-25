<x-account-wrapper pageTitle="{{ __('Tags') }}">
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (!Auth::user()->tags->isEmpty())
                <div class="flex justify-end">
                    <a href="{{ route('tags.create') }}" wire:navigate>
                        <x-primary-button>
                            {{ __('Create Tag') }}
                        </x-primary-button>
                    </a>
                </div>
            @endif
            @livewire('tags.index-table')
        </div>
    </div>
</x-account-wrapper>
