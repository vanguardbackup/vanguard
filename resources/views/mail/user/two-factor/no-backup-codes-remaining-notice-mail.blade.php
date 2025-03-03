<x-mail::message>
# URGENT: No Backup Codes Remaining - Immediate Action Required

Hey {{ $user->first_name }},

This is a critical security alert: You currently have **no backup codes** remaining for your account. This situation puts your account access at significant risk and requires your immediate attention.

Why this is crucial:
- Backup codes are your emergency access method if you lose your primary two-factor authentication device.
- Without backup codes, you might be locked out of your account if you lose access to your primary 2FA method.

Required Action:
Generate a new set of 10 backup codes immediately to ensure you can regain access to your account in case of an emergency.

<x-mail::button :url="{{ route('profile.mfa') }}">
Generate New Backup Codes Now
</x-mail::button>

After generating your new backup codes:

1. Store them securely in a password manager or a safe physical location.
2. Ensure they're accessible even if you lose your primary device.

Thanks,
<br />
{{ config('app.name') }}
</x-mail::message>
