<x-mail::message>
# {{ __('Backup Connection Failure') }} - {{ $backupDestination->label }} ({{ $backupDestination->type() . __(' Driver') }})

Hey, {{ $user->first_name }}!

Our attempt to connect to your backup destination, {{ $backupDestination->label }}, which utilizes the {{ $backupDestination->type() }} driver, was unsuccessful. The error message we encountered is as follows:

<x-mail::panel>
{{ $errorMessage }}
</x-mail::panel>

Common reasons for this error include:
- Incorrect credentials
- Insufficient permissions

Please double-check any API keys, passwords, or other credentials you have entered for this backup destination. If you are using an S3-compatible destination, please ensure that the bucket exists and that the credentials you have entered have the necessary permissions.

If you are still encountering issues, please contact our support team at {{ config('mail.from.address') }}.

<x-mail::button :url="$url">
{{ __('Update Backup Destination') }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
