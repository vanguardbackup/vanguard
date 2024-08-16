<x-mail::message>
# Two-Factor Authentication Disabled

Hey {{ $user->first_name }},

We're reaching out to inform you that two-factor authentication (2FA) for your account has been disabled.

This change significantly impacts your account security. If you initiated this action, no further steps are required. However, we recommend keeping 2FA enabled for optimal account protection.

Important: If you did not disable 2FA, your account may be compromised. Please take immediate action:

1. Log in to your account
2. Change your password
3. Re-enable two-factor authentication

Stay secure,<br>
{{ config('app.name') }}
</x-mail::message>
