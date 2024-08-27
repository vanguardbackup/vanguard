<x-mail::message>
    # Backup Codes Successfully Regenerated Hey {{ $user->first_name }}, We're confirming that you have successfully
    regenerated your account backup codes. This is an important step in maintaining the security of your account. Key
    Points: 1. Your old backup codes are now invalid and have been replaced with the new set. 2. You have a total of 10
    new backup codes available. Important Next Steps: 1. Store your new backup codes securely. Consider using a password
    manager or a secure physical location. 2. Ensure you can access these codes even if you lose your primary device. 3.
    Never share these codes with anyone. 4. If you printed your old codes, securely destroy that printout. Remember:
    Each backup code can only be used once. After using a code, cross it off your list or delete it from your secure
    storage.

    <x-mail::panel>
        If you did not regenerate these backup codes yourself, your account may be compromised. Please take immediate
        action: 1. Change your password immediately 2. Review your recent account activity
    </x-mail::panel>

    Stay secure,
    <br />
    {{ config('app.name') }}
</x-mail::message>
