<x-mail::message>
# Backup Task Notification ## Task Label:
{{ $backupTaskLog->backupTask->label }}

@if ($backupTaskLog->successful_at)
### Task Status: Successful
@else
### Task Status: Failed
@endif

You can view the task details by clicking the link below:

<x-mail::button :url="route('backup-tasks.index')">View Backup Tasks</x-mail::button>

Thanks,
<br />
{{ config('app.name') }}
</x-mail::message>
