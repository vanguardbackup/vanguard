<x-mail::message>
# Backup Code Consumed

Hey {{ $user->first_name }},

We're writing to inform you that a backup code for your account has just been used for authentication.

Current status of your backup codes:
- Remaining: {{ $backupCodesRemainingCount }}
- Used: {{ $backupCodesConsumedCount }}

To ensure continued account security, consider regenerating your backup codes. You can do this by visiting your <a href="{{ route('profile.mfa') }}">2FA account settings</a>.

Important: If you did not use this backup code, please take immediate action to secure your account.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
