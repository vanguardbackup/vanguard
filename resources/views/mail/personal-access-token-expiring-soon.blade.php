@component('mail::message')
# Your API Token is Expiring Soon

Hey {{ $userName }},

Your API token named "{{ $tokenName }}" is set to expire soon. Here are the details:

- **Created on:** {{ $createdAt }}
- **Last used:** {{ $lastUsedAt }}
- **Expires on:** {{ $expiresAt }} (in {{ $daysUntilExpiration }} days)

Please take action to renew or replace this token to ensure uninterrupted access to our API.

@component('mail::button', ['url' => $manageTokensUrl])
Manage Your Tokens
@endcomponent

If you no longer need this token, you can safely ignore this message.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
