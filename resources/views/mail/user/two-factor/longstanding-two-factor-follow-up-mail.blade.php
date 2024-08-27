<x-mail::message>
    # Annual Backup Codes Review Hey {{ $user->first_name }}, It's been a year since you last generated your account
    backup codes. To ensure the continued security of your account, we recommend reviewing and potentially updating
    these codes. Key points to consider: 1. Verify that you still have access to your backup codes. 2. Check if you've
    used any codes in the past year. 3. Ensure your codes are stored in a secure location. If you can't locate your
    codes or have used some, it's time to generate a new set.

    <x-mail::button :url="{{ route('profile.mfa') }}">Generate New Backup Codes</x-mail::button>

    Remember, backup codes are crucial for account recovery if you lose access to your primary two-factor authentication
    method. Keeping them up-to-date and secure is an important part of maintaining your account's safety. Stay secure,
    <br />
    {{ config('app.name') }}
</x-mail::message>
