<x-mail::message>
    # Security Alert: Backup Codes Viewed Hey {{ $user->first_name }}, We're notifying you that the backup codes linked
    to your account were recently viewed. If this was you: 1. Ensure you've stored the backup codes securely, preferably
    in a password manager or a safe physical location. 2. Remember that each backup code can only be used once. 3.
    Consider regenerating your backup codes if you suspect they might have been compromised.

    <x-mail::panel>
        If you did not view these backup codes: Your account security may be at risk. Please take immediate action: 1.
        Change your password immediately 2. Enable two-factor authentication if it's not already active 3. Regenerate
        your backup codes
    </x-mail::panel>

    You can manage your backup codes and other security settings by visiting your
    <a href="{{ route('profile.mfa') }}">account security page</a>
    . Stay vigilant,
    <br />
    {{ config('app.name') }}
</x-mail::message>
