<x-mail::message>
    # {{ __('Backup task failed') }} Hey {{ $user->first_name }}, Your backup task "{{ $taskName }}" has failed. Here
    are the details:

    <x-mail::panel>
        ## Error Message:

        {{ $errorMessage }}
    </x-mail::panel>

    You can view the full task log by checking the run log for the particular task.

    <x-mail::button :url="route('backup-tasks.index')">View Backup Tasks</x-mail::button>

    Thanks,
    <br />
    {{ config('app.name') }}
</x-mail::message>
