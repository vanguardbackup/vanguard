<x-mail::message>
# Suspension Lifted

Hello {{ $user->first_name }},

Your suspension has been lifted. You are now able to log in.

Please be aware that any future breaking of rules will result in your account being suspended once more.

{{ config('app.name') }}
</x-mail::message>
