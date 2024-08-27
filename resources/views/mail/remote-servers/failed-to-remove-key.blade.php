<x-mail::message>
    # {{ $remoteServer->label }} - Failed to Remove Key Hey, {{ $user->first_name }}! We have failed to remove our SSH
    key from the server: {{ $remoteServer->label }}. You can find the error message below:

    <x-mail::panel>
        {{ $message }}
    </x-mail::panel>

    Please connect to {{ $remoteServer->label }} through your preferred SSH client and remove the key manually by
    navigating to the `~/.ssh/authorized_keys` file. Thanks,
    <br />
    {{ config('app.name') }}
</x-mail::message>
