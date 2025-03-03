<x-mail::message>
# Two-Factor Authentication Disabled

Hey {{ $user->first_name }},

We're reaching out to inform you that two-factor authentication (2FA) for your account has been disabled.

This action was performed by an administrator.

If you did not request this change, please contact your system administrator immediately or log in to your account and re-enable 2FA.

For security best practices, we recommend keeping two-factor authentication enabled on your account.

Thanks,
<br />
{{ config('app.name') }}
</x-mail::message>
