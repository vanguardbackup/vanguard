@section('title', __('Update Backup Task'))
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Update Backup Task') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @livewire('backup-tasks.forms.update-backup-task-form', ['backupTask' => $backupTask])
            @livewire('backup-tasks.forms.delete-backup-task-form', ['backupTask' => $backupTask])

        </div>
    </div>
</x-app-layout>
