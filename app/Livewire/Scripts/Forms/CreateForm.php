<?php

declare(strict_types=1);

namespace App\Livewire\Scripts\Forms;

use App\Models\BackupTask;
use App\Models\Script;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Masmerise\Toaster\Toaster;

/**
 * Livewire component for creating a new script.
 *
 * This component handles the form submission and validation for creating a new script.
 */
class CreateForm extends Component
{
    /** @var string The label for the new script. */
    public string $label = '';

    /** @var string The script itself. */
    public string $script = '';

    /** @var string Type - where it should be triggered */
    public string $type = '';

    /** @var array<int, bool> Backup Tasks - which tasks should it be attached to? */
    public array $selectedTasks = [];

    /** @var Collection<int, BackupTask> Cache for backup tasks */
    public Collection $backupTasks;

    /** @var array<int, array<string, mixed>> Existing script associations */
    public array $existingAssociations = [];

    /**
     * Initialize the component state.
     */
    public function mount(): void
    {
        $this->type = Script::TYPE_PRESCRIPT;

        $this->backupTasks = BackupTask::orderBy('label')->get();

        // Initialize selectedTasks with empty array if no backup tasks exist
        if ($this->backupTasks->isEmpty()) {
            $this->selectedTasks = [];
        } else {
            $this->selectedTasks = array_fill_keys(
                $this->backupTasks->pluck('id')->toArray(),
                false
            );
        }

        $this->loadExistingAssociations();
    }

    /**
     * When script type changes, refresh the existing associations warning.
     */
    public function updatedType(): void
    {
        $this->loadExistingAssociations();
    }

    /**
     * Sets a basic script template based on the selected type.
     *
     * @param  string  $templateType  The type of template to set
     */
    public function setScriptTemplate(string $templateType): void
    {
        $templates = [
            'bash' => "#!/bin/bash\n\n# Your bash script here\n\nexit 0",
            'php' => "#!/usr/bin/env php\n<?php\n\n// Your PHP code here\n\nexit(0);",
            'python' => "#!/usr/bin/env python3\n\nimport sys\n\n# Your Python code here\n\nsys.exit(0)",
            'node' => "#!/usr/bin/env node\n\n// Your Node.js code here\n\nprocess.exit(0);",
        ];

        if (isset($templates[$templateType])) {
            $this->script = $templates[$templateType];
        }
    }

    /**
     * Handle the form submission for creating a new script.
     *
     * Validates the input, creates a new Script, and redirects to the index page.
     */
    public function submit(): RedirectResponse|Redirector
    {
        $this->validate([
            'label' => ['required', 'string'],
            'script' => ['required', 'string'],
            'type' => ['required', 'string', 'in:' . Script::TYPE_PRESCRIPT . ',' . Script::TYPE_POSTSCRIPT],
            'selectedTasks' => ['array'],
        ], [
            'label.required' => __('Please enter a label.'),
            'script.required' => __('Please enter a script.'),
            'type.required' => __('Please select a script type.'),
        ]);

        $script = Script::create([
            'user_id' => Auth::id(),
            'label' => $this->label,
            'script' => $this->script,
            'type' => $this->type,
        ]);

        $taskIds = array_keys(array_filter($this->selectedTasks));
        if ($taskIds !== []) {
            // For each selected task, detach any existing script of the same type
            foreach ($taskIds as $taskId) {
                $task = BackupTask::find($taskId);

                // Skip if task doesn't exist
                if (! $task) {
                    continue;
                }

                // Detach existing scripts of the same type
                $existingScripts = $task->scripts()->where('type', $this->type)->get();
                if ($existingScripts->isNotEmpty()) {
                    $task->scripts()->detach($existingScripts->pluck('id'));
                }
            }

            $script->backupTasks()->attach($taskIds);
        }

        Toaster::success('The script :label has been added.', ['label' => $script->getAttribute('label')]);

        return Redirect::route('scripts.index');
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.scripts.forms.create-script', [
            'backupTasks' => $this->backupTasks,
            'existingAssociations' => $this->existingAssociations,
        ]);
    }

    /**
     * Load existing script associations for backup tasks.
     */
    protected function loadExistingAssociations(): void
    {
        $this->existingAssociations = [];

        foreach ($this->backupTasks as $backupTask) {
            // Ensure we have a valid backup task
            if (! $backupTask) {
                continue;
            }

            $prescriptId = $backupTask->scripts()
                ->where('type', Script::TYPE_PRESCRIPT)
                ->first()?->id;

            $postscriptId = $backupTask->scripts()
                ->where('type', Script::TYPE_POSTSCRIPT)
                ->first()?->id;

            if ($prescriptId || $postscriptId) {
                $this->existingAssociations[$backupTask->getAttribute('id')] = [
                    Script::TYPE_PRESCRIPT => $prescriptId ? [
                        'id' => $prescriptId,
                        'label' => Script::find($prescriptId)?->getAttribute('label') ?? 'Unknown',
                    ] : null,
                    Script::TYPE_POSTSCRIPT => $postscriptId ? [
                        'id' => $postscriptId,
                        'label' => Script::find($postscriptId)?->getAttribute('label') ?? 'Unknown',
                    ] : null,
                ];
            }
        }
    }
}
