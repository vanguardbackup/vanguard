<x-mail::message>
    # Two-Factor Authentication Successfully Enabled Hey {{ $user->first_name }}, Great news! Two-factor authentication
    (2FA) has been successfully enabled for your account. This significant security enhancement will help protect your
    account from unauthorized access. Key points to remember: 1. Always have your second factor (e.g., authenticator
    app, security key) available when logging in. 2. Store your backup codes in a safe place in case you lose access to
    your primary 2FA method. 3. If you use an authenticator app, ensure it's backed up or remember to transfer it when
    changing devices. If you did not enable 2FA yourself, please take immediate action to secure your account: 1. Log in
    to your account (if possible) 2. Change your password 3. Review and update your security settings 4. Contact our
    support team immediately for assistance Strengthening your account security is a great step. If you have any
    questions about using 2FA, please don't hesitate to reach out to our support team. Stay secure,
    <br />
    {{ config('app.name') }}
</x-mail::message>
