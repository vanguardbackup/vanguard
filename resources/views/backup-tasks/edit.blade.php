@section('title', __('Update Backup Task'))
<x-app-layout>
    <x-slot name="header">
        {{ __('Update Backup Task') }}
    </x-slot>
    <div>
        @livewire('backup-tasks.forms.update-backup-task-form', ['backupTask' => $backupTask])
        @livewire('backup-tasks.forms.delete-backup-task-form', ['backupTask' => $backupTask])
    </div>
</x-app-layout>
