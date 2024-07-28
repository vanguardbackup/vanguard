@section('title', $header )
<x-account-wrapper pageTitle=" {{ $header }}">
    <div class="py-4 sm:py-6">
        {{ $slot }}
    </div>
</x-account-wrapper>
