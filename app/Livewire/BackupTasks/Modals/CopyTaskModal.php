<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Modals;

use App\Models\BackupTask;
use App\Models\User;
use App\Rules\UniqueScheduledTimePerRemoteServer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Toaster;

class CopyTaskModal extends Component
{
    public ?int $backupTaskToCopyId = null;
    public ?string $optionalNewLabel = null;
    public string $frequency = 'daily';
    public string $timeToRun = '00:00';
    /** @var Collection<int, BackupTask> */
    public Collection $backupTasks;

    public function mount(): void
    {
        $this->resetModal();
    }

    #[On('open-modal.copy-backup-task')]
    public function openModal(): void
    {
        $this->resetModal();
    }

    public function resetModal(): void
    {
        $this->reset(['backupTaskToCopyId', 'optionalNewLabel', 'frequency', 'timeToRun']);
        $user = Auth::user();
        $this->backupTasks = $user instanceof User ? $user->getAttribute('backupTasks') : new Collection;
    }

    public function updatedBackupTaskToCopyId(?int $value): void
    {
        if ($value === null) {
            return;
        }

        $task = BackupTask::find($value);
        if ($task) {
            $this->frequency = $task->frequency ?? 'daily';
            $this->timeToRun = $task->time_to_run_at ?? '00:00';
        }
    }

    public function copyTask(): void
    {
        $this->validate();

        $originalTask = BackupTask::findOrFail($this->backupTaskToCopyId);

        $newTask = $originalTask->replicate();
        $newTask->label = $this->optionalNewLabel ?: $originalTask->label . ' (Copy)';
        $newTask->frequency = $this->frequency;
        $newTask->time_to_run_at = $this->timeToRun;
        $newTask->custom_cron_expression = null; // Reset custom cron expression
        $newTask->save();

        // Copy relationships
        $newTask->tags()->sync($originalTask->tags);
        $newTask->notificationStreams()->sync($originalTask->notificationStreams);

        $this->dispatch('task-copied');
        $this->dispatch('close-modal', 'copy-backup-task');
        $this->resetModal();
        $this->dispatch('refreshBackupTasksTable');

        Toaster::success('The backup task has been copied.');
    }

    public function render(): View
    {
        return view('livewire.backup-tasks.modals.copy-task-modal', [
            'backupTasks' => $this->backupTasks,
            'backupTimes' => $this->getBackupTimes(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'backupTaskToCopyId' => ['required', 'exists:backup_tasks,id'],
            'optionalNewLabel' => ['nullable', 'string', 'max:255'],
            'frequency' => ['required', 'string', Rule::in(['daily', 'weekly'])],
            'timeToRun' => [
                'required',
                'string',
                'regex:/^([01]?\d|2[0-3]):([0-5]\d)$/',
                new UniqueScheduledTimePerRemoteServer((int) $this->getRemoteServerId()),
            ],
        ];
    }

    private function getRemoteServerId(): ?int
    {
        return BackupTask::find($this->backupTaskToCopyId)?->remote_server_id;
    }

    /**
     * @return Collection<int, string>
     */
    private function getBackupTimes(): Collection
    {
        return collect(range(0, 95))->map(function (int $quarterHour): string {
            return sprintf('%02d:%02d', intdiv($quarterHour, 4), ($quarterHour % 4) * 15);
        });
    }
}
