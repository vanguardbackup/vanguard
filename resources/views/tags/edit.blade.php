@section('title', 'Update Tag')
<x-account-wrapper pageTitle="{{ __('Update Tag') }}">
    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @livewire('tags.update-form', ['tag' => $tag])
        </div>
    </div>
</x-account-wrapper>
