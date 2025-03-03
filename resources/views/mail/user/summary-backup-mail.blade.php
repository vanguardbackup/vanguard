<x-mail::message>
# Your Weekly Backup Performance Recap

Hey {{ $user->first_name }},

Here's a summary of your backup activities from {{ $data['date_range']['start'] }} to {{ $data['date_range']['end'] }}.

## Quick Stats
- Total Backups: {{ $data['total_tasks'] }}
- Successful: {{ $data['successful_tasks'] }}
- Failed: {{ $data['failed_tasks'] }}

## Performance Breakdown

<x-mail::panel>
Success Rate: {{ number_format($data['success_rate'], 1) }}%

@if ($data['success_rate'] === 100)
🎉 Perfect score! All your backup tasks were successful this week.
@elseif ($data['success_rate'] >= 90)
👍 Great job! Most of your backup tasks were successful.
@elseif ($data['success_rate'] >= 75)
🔍 Good, but there's room for improvement. Check your failed backups logs.
@else
⚠️ Attention needed: A significant number of your backups failed this week.
@endif
</x-mail::panel>

@if ($data['failed_tasks'] > 0)
## Action Required
Some of your backup tasks failed this week. We recommend reviewing your backup settings and logs to address any issues to ensure the safety of your data.

<x-mail::button :url="route('backup-tasks.index')">
Review Backup Tasks
</x-mail::button>
@else
## Keep Up the Good Work!
All your backups were successful this week. Regular backups are crucial for data safety.

 <x-mail::button :url="route('overview')">
View Overview
</x-mail::button>
@endif

Thank you for using {{ config('app.name') }} to keep your data safe and secure.

Thanks,
<br />
{{ config('app.name') }}
</x-mail::message>
