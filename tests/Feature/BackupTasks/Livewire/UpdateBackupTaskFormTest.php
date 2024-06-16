<?php

use App\Livewire\BackupTasks\UpdateBackupTaskForm;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\RemoteServer;
use App\Models\User;
use Livewire\Livewire;

function createUserWithBackupTaskAndDependencies(): array
{
    $user = User::factory()->create();
    $remoteServer = RemoteServer::factory()->create(['user_id' => $user->id]);
    $backupTask = BackupTask::factory()->create([
        'user_id' => $user->id,
        'remote_server_id' => $remoteServer->id,
        'maximum_backups_to_keep' => 5,
        'source_path' => null,
    ]);
    $backupDestination = BackupDestination::factory()->create(['user_id' => $user->id]);

    return [
        'user' => $user,
        'remoteServer' => $remoteServer,
        'backupTask' => $backupTask,
        'backupDestination' => $backupDestination,
    ];
}

beforeEach(function () {
    $this->data = createUserWithBackupTaskAndDependencies();
    $this->actingAs($this->data['user']);
});

test('form can be rendered', function () {
    Livewire::test(UpdateBackupTaskForm::class, [
        'backupTask' => $this->data['backupTask'],
        'remoteServers' => $this->data['user']->remoteServers,
    ])->assertOk();
});

test('backup task can be updated by the owner', function () {
    $livewire = Livewire::test(UpdateBackupTaskForm::class, [
        'backupTask' => $this->data['backupTask'],
        'remoteServers' => $this->data['user']->remoteServers,
    ]);

    $livewire->set('label', 'Updated Label')
        ->set('description', 'Updated Description')
        ->set('remoteServerId', $this->data['remoteServer']->id)
        ->set('backupDestinationId', $this->data['backupDestination']->id)
        ->set('frequency', BackupTask::FREQUENCY_WEEKLY)
        ->set('timeToRun', '12:00')
        ->set('backupsToKeep', 10)
        ->set('backupType', BackupTask::TYPE_DATABASE)
        ->set('databaseName', 'database_name')
        ->set('appendedFileName', 'appended_file_name')
        ->set('useCustomCron', false)
        ->set('notifyEmail', 'alerts@email.com')
        ->set('notifyDiscordWebhook', 'https://discord.com/api/webhooks/1234567890/ABC123')
        ->set('notifySlackWebhook', 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXX')
        ->set('storePath', '/my-cool-backups')
        ->set('excludedDatabaseTables', 'table1,table2')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('backup_tasks', [
        'label' => 'Updated Label',
        'description' => 'Updated Description',
        'remote_server_id' => $this->data['remoteServer']->id,
        'backup_destination_id' => $this->data['backupDestination']->id,
        'frequency' => BackupTask::FREQUENCY_WEEKLY,
        'time_to_run_at' => '12:00',
        'maximum_backups_to_keep' => 10,
        'type' => BackupTask::TYPE_DATABASE,
        'database_name' => 'database_name',
        'appended_file_name' => 'appended_file_name',
        'notify_email' => 'alerts@email.com',
        'notify_discord_webhook' => 'https://discord.com/api/webhooks/1234567890/ABC123',
        'notify_slack_webhook' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXX',
        'store_path' => '/my-cool-backups',
        'excluded_database_tables' => 'table1,table2',
    ]);
});

test('backup task can be updated by the owner with custom cron', function () {
    $livewire = Livewire::test(UpdateBackupTaskForm::class, [
        'backupTask' => $this->data['backupTask'],
        'remoteServers' => $this->data['user']->remoteServers,
    ]);

    $livewire->set('label', 'Updated Label')
        ->set('description', 'Updated Description')
        ->set('remoteServerId', $this->data['remoteServer']->id)
        ->set('backupDestinationId', $this->data['backupDestination']->id)
        ->set('cronExpression', '0 0 * * *')
        ->set('backupsToKeep', 10)
        ->set('backupType', BackupTask::TYPE_DATABASE)
        ->set('databaseName', 'database_name')
        ->set('appendedFileName', 'appended_file_name')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('backup_tasks', [
        'label' => 'Updated Label',
        'description' => 'Updated Description',
        'remote_server_id' => $this->data['remoteServer']->id,
        'backup_destination_id' => $this->data['backupDestination']->id,
        'custom_cron_expression' => '0 0 * * *',
        'maximum_backups_to_keep' => 10,
        'type' => BackupTask::TYPE_DATABASE,
        'database_name' => 'database_name',
        'appended_file_name' => 'appended_file_name',
    ]);
});

test('backup task cannot be updated by another user', function () {
    $anotherUser = User::factory()->create();

    $this->actingAs($anotherUser);

    $livewire = Livewire::test(UpdateBackupTaskForm::class, [
        'backupTask' => $this->data['backupTask'],
        'remoteServers' => $anotherUser->remoteServers,
    ]);

    $livewire->set('label', 'Updated Label')
        ->set('description', 'Updated Description')
        ->set('remoteServerId', $this->data['remoteServer']->id)
        ->set('backupDestinationId', BackupDestination::factory()->create()->id)
        ->set('frequency', BackupTask::FREQUENCY_WEEKLY)
        ->set('timeToRun', '12:00')
        ->set('backupsToKeep', 10)
        ->set('backupType', BackupTask::TYPE_DATABASE)
        ->set('databaseName', 'database_name')
        ->set('appendedFileName', 'appended_file_name')
        ->call('submit')
        ->assertForbidden();
});

test('backup task has required validation rules', function () {
    $livewire = Livewire::test(UpdateBackupTaskForm::class, [
        'backupTask' => $this->data['backupTask'],
        'remoteServers' => RemoteServer::all(),
    ]);

    $livewire->set('label', '')
        ->set('backupDestinationId', '')
        ->set('backupType', '')
        ->call('submit')
        ->assertHasErrors([
            'label' => 'required',
            'backupDestinationId' => 'required',
            'backupType' => 'required',
        ]);
});

test('discord webhook url must be valid', function () {
    $livewire = Livewire::test(UpdateBackupTaskForm::class, [
        'backupTask' => $this->data['backupTask'],
        'remoteServers' => RemoteServer::all(),
    ]);

    $livewire->set('notifyDiscordWebhook', 'invalid-discord-url')
        ->call('submit')
        ->assertHasErrors([
            'notifyDiscordWebhook' => 'starts_with:https://discord.com/api/webhooks/',
        ]);
});

test('slack webhook url must be valid', function () {
    $livewire = Livewire::test(UpdateBackupTaskForm::class, [
        'backupTask' => $this->data['backupTask'],
        'remoteServers' => RemoteServer::all(),
    ]);

    $livewire->set('notifySlackWebhook', 'invalid-slack-url')
        ->call('submit')
        ->assertHasErrors([
            'notifySlackWebhook' => 'starts_with:https://hooks.slack.com/services/',
        ]);
});

test('the time to run at is converted from the users timezone to UTC', function () {

    $this->data['user']->update(['timezone' => 'America/New_York']);

    $livewire = Livewire::test(UpdateBackupTaskForm::class, [
        'backupTask' => $this->data['backupTask'],
        'remoteServers' => RemoteServer::all(),
    ]);

    $livewire->set('timeToRun', '12:00') // 12:00 PM in America/New_York
        ->set('description', '')
        ->set('sourcePath', '/var/www/html')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('backup_tasks', [
        'time_to_run_at' => '16:00', // 4:00 PM in UTC
    ]);
});

test('the store path needs to be a valid unix path', function () {

    $livewire = Livewire::test(UpdateBackupTaskForm::class, [
        'backupTask' => $this->data['backupTask'],
        'remoteServers' => RemoteServer::all(),
    ]);

    $livewire->set('sourcePath', 'C:\var\www\html')
        ->call('submit')
        ->assertHasErrors([
            'sourcePath' => 'regex:/^\/[a-zA-Z0-9_\/]+$/', // Unix path
        ]);
});

test('the excluded database tables must be a valid comma separated list', function () {
    $livewire = Livewire::test(UpdateBackupTaskForm::class, [
        'backupTask' => $this->data['backupTask'],
        'remoteServers' => RemoteServer::all(),
    ]);

    $livewire->set('excludedDatabaseTables', 'table1, table2, table3')
        ->set('sourcePath', '/var/www/html')
        ->set('description', '')
        ->call('submit')
        ->assertHasErrors([
            'excludedDatabaseTables' => 'regex:/^([a-zA-Z0-9_]+,? ?)+$/', // Comma separated list
        ]);
});

test('a task cannot share the same time as another task on the same server', function () {
    $this->withoutExceptionHandling();
    $user = User::factory()->create();

    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
    ]);

    BackupTask::factory()->create([
        'remote_server_id' => $remoteServer->id,
        'time_to_run_at' => '12:00',
        'user_id' => $user->id,
    ]);

    $backupTaskTwo = BackupTask::factory()->create([
        'remote_server_id' => $remoteServer->id,
        'time_to_run_at' => '13:00',
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('backup_tasks', [
        'time_to_run_at' => '12:00',
    ]);

    $this->actingAs($user);

    Livewire::test(UpdateBackupTaskForm::class, [
        'backupTask' => $backupTaskTwo,
        'remoteServers' => RemoteServer::all(),
    ])
        ->set('timeToRun', '12:00')
        ->call('submit')
        ->assertHasErrors('timeToRun');
});

test('a task retains its set time without validation errors', function () {

    $user = User::factory()->create();

    $remoteServer = RemoteServer::factory()->create([
        'user_id' => $user->id,
    ]);

    $backupTask = BackupTask::factory()->create([
        'remote_server_id' => $remoteServer->id,
        'time_to_run_at' => '12:00',
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('backup_tasks', [
        'time_to_run_at' => '12:00',
    ]);

    $this->actingAs($user);

    Livewire::test(UpdateBackupTaskForm::class, [
        'backupTask' => $backupTask,
        'remoteServers' => RemoteServer::all(),
    ])
        ->set('timeToRun', '12:00')
        ->set('sourcePath', '/var/www/html')
        ->set('description', '')
        ->call('submit')
        ->assertHasNoErrors();
});
