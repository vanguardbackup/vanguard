<x-mail::message>
# Hello {{ $first_name }},

Your Quiet Mode period has ended. Here's what this means:

- You'll now receive notifications for any new backup tasks.
- You can review any backups that occurred during your Quiet Mode period.

Remember, you can always reactivate Quiet Mode if you need to pause notifications again.

Best regards,<br>
The {{ config('app.name') }} Team
</x-mail::message>
