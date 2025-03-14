<x-mail::message>
# Your Account Has Been Successfully Deleted

Hello {{ $user->first_name }},

We're confirming that your account and all associated personal information has been permanently removed from {{ config('app.name') }}.

## What This Means:
- Your personal data has been completely erased from our servers
 - Your account can no longer be accessed
- No further action is required from you

If you have any questions about the deletion process or need additional assistance, please open a question in our [GitHub discussions](https://github.com/orgs/vanguardbackup/discussions/new?category=q-a).

Thank you for your time with us.

{{ config('app.name') }}
</x-mail::message>
