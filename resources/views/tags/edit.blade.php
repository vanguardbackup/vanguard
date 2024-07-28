@section('title', 'Update Tag')
<x-account-wrapper pageTitle="{{ __('Update Tag') }}">
    @livewire('tags.update-form', ['tag' => $tag])
</x-account-wrapper>
