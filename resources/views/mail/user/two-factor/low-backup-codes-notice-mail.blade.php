<x-mail::message>
    # Low Backup Codes Alert: Action Required Hey {{ $user->first_name }}, We've noticed that you have very few backup
    codes remaining for your account. This situation requires your immediate attention to ensure uninterrupted access to
    your account. Why this matters: Backup codes are crucial for regaining access to your account if you lose your
    primary two-factor authentication method. Having an adequate supply of these codes is essential for maintaining the
    security and accessibility of your account. Recommended action: Generate a new set of 10 backup codes immediately.
    This will ensure you have enough codes for future use.

    <x-mail::button :url="{{ route('profile.mfa') }}">Generate New Backup Codes</x-mail::button>

    After generating new codes: 1. Store them securely in a password manager or a safe physical location. 2. Delete any
    previously used or potentially compromised codes. 3. Consider setting up an additional two-factor authentication
    method for added security. Stay secure,
    <br />
    {{ config('app.name') }}
</x-mail::message>
