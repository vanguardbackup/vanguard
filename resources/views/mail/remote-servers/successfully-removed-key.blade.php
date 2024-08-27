<x-mail::message>
    # {{ $remoteServer->label }} - Successfully Removed Key Hey, {{ $user->first_name }}! We have successfully removed
    our SSH key from the server: {{ $remoteServer->label }}. If you have any questions, please let us know. Thanks,
    <br />
    {{ config('app.name') }}
</x-mail::message>
