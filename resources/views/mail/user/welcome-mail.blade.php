<x-mail::message>
# Welcome to {{ config('app.name') }}!

Hey, {{ $user->first_name }}!

Thank you so much for creating an account on {{ config('app.name') }}. We are thrilled to have you on board!

{{ config('app.name') }} was born out of a desire to create a simple, no-cost solution for backing up files and databases from servers. We hope you find it just as useful and convenient.

If you ever have any questions or feedback, please don’t hesitate to reach out. We’d love to hear from you.

To get started, click the button below to link your first remote server to {{ config('app.name') }}.

<x-mail::button :url="$url">
{{ __('Add Remote Server') }}
</x-mail::button>

Thanks,
<br />
{{ config('app.name') }}
</x-mail::message>
