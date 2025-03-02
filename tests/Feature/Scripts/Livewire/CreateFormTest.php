<?php

declare(strict_types=1);

use App\Models\BackupTask;
use App\Models\Script;
use App\Models\User;

test('the component can be rendered', function (): void {
    BackupTask::factory()->create();

    $livewire = Livewire::test('scripts.forms.create-form');

    $livewire->assertOk();
});

test('a user can create a new script', function (): void {
    $user = User::factory()->create();

    $backupTask = BackupTask::factory()->create([
        'user_id' => $user->id,
    ]);

    $livewire = Livewire::actingAs($user)
        ->test('scripts.forms.create-form')
        ->set('label', 'New Script')
        ->set('script', 'blah blah')
        ->set('type', Script::TYPE_PRESCRIPT)
        ->set('selectedTasks', [
            $backupTask->getAttribute('id') => true,
        ])
        ->call('submit');

    $this->assertDatabaseHas('scripts', [
        'label' => 'New Script',
        'script' => 'blah blah',
        'type' => Script::TYPE_PRESCRIPT,
        'user_id' => $user->getAttribute('id'),
    ]);

    $this->assertDatabaseCount('backup_task_script', 1);

    $livewire->assertRedirect(route('scripts.index'));
});

test('a label is required', function (): void {
    $user = User::factory()->create();

    BackupTask::factory()->create([
        'user_id' => $user->getAttribute('id'),
    ]);

    Livewire::actingAs($user)
        ->test('scripts.forms.create-form')
        ->set('script', 'Test Script Content')
        ->set('type', Script::TYPE_PRESCRIPT)
        ->call('submit')
        ->assertHasErrors(['label' => 'required']);
});

test('a script is required', function (): void {
    $user = User::factory()->create();

    BackupTask::factory()->create([
        'user_id' => $user->getAttribute('id'),
    ]);

    Livewire::actingAs($user)
        ->test('scripts.forms.create-form')
        ->set('label', 'Test Script')
        ->set('type', Script::TYPE_PRESCRIPT)
        ->call('submit')
        ->assertHasErrors(['script' => 'required']);
});

test('the component can be rendered when no backup tasks exist', function (): void {
    // Make sure no backup tasks exist
    $this->assertDatabaseCount('backup_tasks', 0);

    $livewire = Livewire::test('scripts.forms.create-form');

    $livewire->assertOk();
});

test('a user can create a script without assigning it to any backup tasks', function (): void {
    $user = User::factory()->create();

    $livewire = Livewire::actingAs($user)
        ->test('scripts.forms.create-form')
        ->set('label', 'New Standalone Script')
        ->set('script', 'echo "This is a standalone script"')
        ->set('type', Script::TYPE_PRESCRIPT)
        ->call('submit');

    $this->assertDatabaseHas('scripts', [
        'label' => 'New Standalone Script',
        'script' => 'echo "This is a standalone script"',
        'type' => Script::TYPE_PRESCRIPT,
        'user_id' => $user->getAttribute('id'),
    ]);

    // Verify no backup task associations were created
    $this->assertDatabaseCount('backup_task_script', 0);

    $livewire->assertRedirect(route('scripts.index'));
});

test('assigning a new script to a backup task replaces existing script of the same type', function (): void {
    $user = User::factory()->create();

    // Create a backup task
    $backupTask = BackupTask::factory()->create([
        'user_id' => $user->getAttribute('id'),
    ]);

    // Create an existing script (pre-script) and attach it to the backup task
    $existingScript = Script::factory()->create([
        'user_id' => $user->getAttribute('id'),
        'label' => 'Existing Pre-script',
        'script' => 'echo "This is the original script"',
        'type' => Script::TYPE_PRESCRIPT,
    ]);

    // Attach existing script to the backup task
    $existingScript->backupTasks()->attach($backupTask->getAttribute('id'));

    // Verify the existing association
    $this->assertDatabaseHas('backup_task_script', [
        'backup_task_id' => $backupTask->getAttribute('id'),
        'script_id' => $existingScript->getAttribute('id'),
    ]);

    // Create a new script of the same type
    Livewire::actingAs($user)
        ->test('scripts.forms.create-form')
        ->set('label', 'New Replacement Script')
        ->set('script', 'echo "This is the replacement script"')
        ->set('type', Script::TYPE_PRESCRIPT)
        ->set('selectedTasks', [
            $backupTask->getAttribute('id') => true,
        ])
        ->call('submit');

    // Get the newly created script
    $newScript = Script::where('label', 'New Replacement Script')->first();

    // Verify the old script association is removed
    $this->assertDatabaseMissing('backup_task_script', [
        'backup_task_id' => $backupTask->getAttribute('id'),
        'script_id' => $existingScript->getAttribute('id'),
    ]);

    // Verify the new script is associated
    $this->assertDatabaseHas('backup_task_script', [
        'backup_task_id' => $backupTask->getAttribute('id'),
        'script_id' => $newScript->getAttribute('id'),
    ]);
});
