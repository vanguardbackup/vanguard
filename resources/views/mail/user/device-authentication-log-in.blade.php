<x-mail::message>
# Device Login

Hey {{ $user->first_name }},

We noticed a new login to your account from a mobile device.

If this was you, no further action is required. However, if you do not recognize this activity, we strongly recommend that you review your account settings and update your password immediately.

To manage your API tokens and review any recent activity, please click the button below:

<x-mail::button :url="route('profile.api')">
Review API Tokens
</x-mail::button>

Thanks,
<br />
{{ config('app.name') }}
</x-mail::message>
