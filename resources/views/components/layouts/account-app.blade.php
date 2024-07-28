@section('title', $header )
<x-account-wrapper pageTitle="{{ $header }}">
    @if(isset($action))
        <x-slot name="action">
           {{ $action }}
        </x-slot>
    @endif
    <div>
        {{ $slot }}
    </div>
</x-account-wrapper>
