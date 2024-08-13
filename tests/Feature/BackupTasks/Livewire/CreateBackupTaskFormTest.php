<?php

declare(strict_types=1);

use App\Livewire\BackupTasks\Forms\CreateBackupTaskForm;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\NotificationStream;
use App\Models\RemoteServer;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->server = RemoteServer::factory()->create();
    $this->destination = BackupDestination::factory()->create();
    $this->actingAs($this->user);
});

test('form is rendered', function (): void {
    Livewire::test(CreateBackupTaskForm::class)
        ->assertSet('currentStep', 1)
        ->assertSet('totalSteps', 6)
        ->assertStatus(200);
});

test('users can create backup tasks', function (): void {
    $tags = Tag::factory()->count(2)->sequence(
        ['label' => 'Tag 1', 'user_id' => $this->user->id],
        ['label' => 'Tag 2', 'user_id' => $this->user->id]
    )->create();

    $notificationStreams = NotificationStream::factory()->count(2)->sequence(
        ['label' => 'Stream 1', 'user_id' => $this->user->id, 'type' => 'email', 'value' => 'test@email.com'],
        ['label' => 'Stream 2', 'user_id' => $this->user->id, 'type' => 'email', 'value' => 'test2@email.com'],
    )->create();

    Livewire::test(CreateBackupTaskForm::class)
        ->set('label', 'Test Backup Task')
        ->set('description', 'This is a test backup task.')
        ->set('sourcePath', '/var/www/html')
        ->set('remoteServerId', $this->server->id)
        ->set('backupDestinationId', $this->destination->id)
        ->set('frequency', 'daily')
        ->set('timeToRun', '00:00')
        ->set('appendedFileName', 'test-backup')
        ->set('storePath', '/my-cool-backups')
        ->set('excludedDatabaseTables', 'table1,table2')
        ->set('selectedTags', $tags->pluck('id')->toArray())
        ->set('selectedStreams', $notificationStreams->pluck('id')->toArray())
        ->set('encryptionPassword', 'password123')
        ->call('submit');

    $this->assertDatabaseHas('backup_tasks', [
        'user_id' => $this->user->id,
        'remote_server_id' => $this->server->id,
        'backup_destination_id' => $this->destination->id,
        'label' => 'Test Backup Task',
        'description' => 'This is a test backup task.',
        'source_path' => '/var/www/html',
        'frequency' => 'daily',
        'time_to_run_at' => '00:00',
        'custom_cron_expression' => null,
        'appended_file_name' => 'test-backup',
        'store_path' => '/my-cool-backups',
        'excluded_database_tables' => 'table1,table2',
    ]);

    $backupTask = BackupTask::latest()->first();

    $tags->each(function ($tag) use ($backupTask): void {
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag->getAttribute('id'),
            'taggable_id' => $backupTask->id,
            'taggable_type' => BackupTask::class,
        ]);
    });

    $notificationStreams->each(function ($notificationStream) use ($backupTask): void {
        $this->assertDatabaseHas('backup_task_notification_streams', [
            'notification_stream_id' => $notificationStream->getAttribute('id'),
            'backup_task_id' => $backupTask->getAttribute('id'),
        ]);
    });
});

test('users can create backup tasks with a custom cron expression', function (): void {
    Livewire::test(CreateBackupTaskForm::class)
        ->set('label', 'Test Backup Task')
        ->set('description', 'This is a test backup task.')
        ->set('sourcePath', '/var/www/html')
        ->set('remoteServerId', $this->server->id)
        ->set('backupDestinationId', $this->destination->id)
        ->set('frequency', null)
        ->set('cronExpression', '0 0 * * *')
        ->call('submit');

    $this->assertDatabaseHas('backup_tasks', [
        'user_id' => $this->user->id,
        'remote_server_id' => $this->server->id,
        'backup_destination_id' => $this->destination->id,
        'label' => 'Test Backup Task',
        'description' => 'This is a test backup task.',
        'source_path' => '/var/www/html',
        'frequency' => null,
        'time_to_run_at' => null,
        'custom_cron_expression' => '0 0 * * *',
    ]);
});

test('validation is required', function (): void {
    Livewire::test(CreateBackupTaskForm::class)
        ->set('remoteServerId', $this->server->id)
        ->call('submit')
        ->assertHasErrors(['label' => 'required', 'backupDestinationId' => 'required']);
});

test('validation is required unless custom cron expression is set', function (): void {
    Livewire::test(CreateBackupTaskForm::class)
        ->set('cronExpression', '0 0 * * *')
        ->set('remoteServerId', $this->server->id)
        ->call('submit')
        ->assertHasErrors(['label' => 'required', 'backupDestinationId' => 'required']);
});

test('validation is required unless frequency is set', function (): void {
    Livewire::test(CreateBackupTaskForm::class)
        ->set('frequency', 'daily')
        ->set('remoteServerId', $this->server->id)
        ->call('submit')
        ->assertHasErrors(['label' => 'required', 'backupDestinationId' => 'required']);
});

test('validation is required unless time to run is set', function (): void {
    Livewire::test(CreateBackupTaskForm::class)
        ->set('timeToRun', '00:00')
        ->set('remoteServerId', $this->server->id)
        ->call('submit')
        ->assertHasErrors(['label' => 'required', 'backupDestinationId' => 'required']);
});

test('the appended file name cannot contain spaces', function (): void {
    Livewire::test(CreateBackupTaskForm::class)
        ->set('remoteServerId', $this->server->id)
        ->set('appendedFileName', 'test backup')
        ->call('submit')
        ->assertHasErrors(['appendedFileName' => 'alpha_dash']);
});

test("the time to run at is converted to the user's timezone", function (): void {
    $this->user->update(['timezone' => 'America/New_York']);

    Livewire::test(CreateBackupTaskForm::class)
        ->set('label', 'Test Backup Task')
        ->set('sourcePath', '/var/www/html')
        ->set('remoteServerId', $this->server->id)
        ->set('backupDestinationId', $this->destination->id)
        ->set('frequency', 'daily')
        ->set('timeToRun', '00:00')
        ->call('submit');

    $this->assertDatabaseHas('backup_tasks', [
        'time_to_run_at' => '04:00', // 00:00 in America/New_York
    ]);
});

test('the store path needs to be a valid unix path', function (): void {
    Livewire::test(CreateBackupTaskForm::class)
        ->set('label', 'Test Backup Task')
        ->set('sourcePath', '/var/www/html')
        ->set('remoteServerId', $this->server->id)
        ->set('backupDestinationId', $this->destination->id)
        ->set('storePath', 'not-a-valid-path')
        ->call('submit')
        ->assertHasErrors('storePath');
});

test('excluded database tables must be a comma separated list', function (): void {
    Livewire::test(CreateBackupTaskForm::class)
        ->set('label', 'Test Backup Task')
        ->set('sourcePath', '/var/www/html')
        ->set('remoteServerId', $this->server->id)
        ->set('backupDestinationId', $this->destination->id)
        ->set('excludedDatabaseTables', 'table1 table2')
        ->call('submit')
        ->assertHasErrors('excludedDatabaseTables');
});

test('we get a validation error if another task occupies the same time with the same server', function (): void {
    $this->user->backupTasks()->create([
        'label' => 'Test Backup Task',
        'remote_server_id' => $this->server->id,
        'backup_destination_id' => $this->destination->id,
        'time_to_run_at' => '00:00',
    ]);

    Livewire::test(CreateBackupTaskForm::class)
        ->set('label', 'Test Backup Task')
        ->set('sourcePath', '/var/www/html')
        ->set('remoteServerId', $this->server->id)
        ->set('backupDestinationId', $this->destination->id)
        ->set('frequency', 'daily')
        ->set('timeToRun', '00:00')
        ->call('submit')
        ->assertHasErrors('timeToRun');
});

test('users cannot add a tag that does not belong to them', function (): void {
    $tag = Tag::factory()->create();

    Livewire::test(CreateBackupTaskForm::class)
        ->set('label', 'Test Backup Task')
        ->set('sourcePath', '/var/www/html')
        ->set('remoteServerId', $this->server->id)
        ->set('backupDestinationId', $this->destination->id)
        ->set('selectedTags', [$tag->id])
        ->call('submit')
        ->assertHasErrors('selectedTags');
});

test('users cannot add a tag that does not exist', function (): void {
    Livewire::test(CreateBackupTaskForm::class)
        ->set('label', 'Test Backup Task')
        ->set('sourcePath', '/var/www/html')
        ->set('remoteServerId', $this->server->id)
        ->set('backupDestinationId', $this->destination->id)
        ->set('selectedTags', [999])
        ->call('submit')
        ->assertHasErrors('selectedTags');
});

test('users cannot add a stream that does not belong to them', function (): void {
    $stream = NotificationStream::factory()->create();

    Livewire::test(CreateBackupTaskForm::class)
        ->set('label', 'Test Backup Task')
        ->set('sourcePath', '/var/www/html')
        ->set('remoteServerId', $this->server->id)
        ->set('backupDestinationId', $this->destination->id)
        ->set('selectedStreams', [$stream->id])
        ->call('submit')
        ->assertHasErrors('selectedStreams');
});

test('users cannot add a stream that does not exist', function (): void {
    Livewire::test(CreateBackupTaskForm::class)
        ->set('label', 'Test Backup Task')
        ->set('sourcePath', '/var/www/html')
        ->set('remoteServerId', $this->server->id)
        ->set('backupDestinationId', $this->destination->id)
        ->set('selectedStreams', [999])
        ->call('submit')
        ->assertHasErrors('selectedStreams');
});

test('validation is performed at each step', function (): void {
    Livewire::test(CreateBackupTaskForm::class)
        ->set('currentStep', 1)
        ->call('nextStep')
        ->assertHasErrors(['label' => 'required'])
        ->set('label', 'Test Backup Task')
        ->call('nextStep')
        ->assertSet('currentStep', 2)
        ->call('nextStep')
        ->assertHasErrors(['remoteServerId' => 'required', 'backupDestinationId' => 'required']);
});
