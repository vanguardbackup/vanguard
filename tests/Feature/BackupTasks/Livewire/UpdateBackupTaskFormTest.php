<?php

declare(strict_types=1);

use App\Livewire\BackupTasks\Forms\UpdateBackupTaskForm;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\NotificationStream;
use App\Models\RemoteServer;
use App\Models\Tag;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->data = createUserWithBackupTaskAndDependencies();
    $this->actingAs($this->data['user']);
});

test('form can be rendered', function (): void {
    Livewire::test(UpdateBackupTaskForm::class, [
        'backupTask' => $this->data['backupTask'],
        'remoteServers' => $this->data['user']->remoteServers,
    ])->assertOk();
});

describe('backup task update', function (): void {
    test('can be updated by the owner', function (): void {
        $tags = Tag::factory(2)->create(['user_id' => $this->data['user']->id]);
        $tagIds = $tags->pluck('id')->toArray();

        $notificationStreams = NotificationStream::factory(2)->email()->create(['user_id' => $this->data['user']->id]);
        $notificationStreamIds = $notificationStreams->pluck('id')->toArray();

        $testable = Livewire::test(UpdateBackupTaskForm::class, [
            'backupTask' => $this->data['backupTask'],
            'remoteServers' => $this->data['user']->remoteServers,
            'availableTags' => $this->data['user']->tags,
            'availableStreams' => $this->data['user']->notificationStreams,
        ]);

        $updatedData = [
            'label' => 'Updated Label',
            'description' => 'Updated Description',
            'remoteServerId' => $this->data['remoteServer']->id,
            'backupDestinationId' => $this->data['backupDestination']->id,
            'frequency' => BackupTask::FREQUENCY_WEEKLY,
            'timeToRun' => '12:00',
            'backupsToKeep' => 10,
            'backupType' => BackupTask::TYPE_DATABASE,
            'databaseName' => 'database_name',
            'appendedFileName' => 'appended_file_name',
            'useCustomCron' => false,
            'storePath' => '/my-cool-backups',
            'excludedDatabaseTables' => 'table1,table2',
            'selectedTags' => $tagIds,
            'selectedStreams' => $notificationStreamIds,
        ];

        $testable->set($updatedData)->call('submit')->assertHasNoErrors();

        $this->assertDatabaseHas('backup_tasks', [
            'id' => $this->data['backupTask']->id,
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
            'store_path' => '/my-cool-backups',
            'excluded_database_tables' => 'table1,table2',
        ]);

        foreach ($tagIds as $tagId) {
            $this->assertDatabaseHas('taggables', [
                'tag_id' => $tagId,
                'taggable_id' => $this->data['backupTask']->id,
                'taggable_type' => BackupTask::class,
            ]);
        }

        foreach ($notificationStreamIds as $notificationStreamId) {
            $this->assertDatabaseHas('backup_task_notification_streams', [
                'notification_stream_id' => $notificationStreamId,
                'backup_task_id' => $this->data['backupTask']->id,
            ]);
        }

        $updatedBackupTask = BackupTask::find($this->data['backupTask']->id);
        expect($updatedBackupTask->tags)->toHaveCount(2)
            ->and($updatedBackupTask->notificationStreams)->toHaveCount(2);
    });

    test('can be updated by the owner with custom cron', function (): void {
        $testable = Livewire::test(UpdateBackupTaskForm::class, [
            'backupTask' => $this->data['backupTask'],
            'remoteServers' => $this->data['user']->remoteServers,
        ]);

        $testable->set('label', 'Updated Label')
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

    test('cannot be updated by another user', function (): void {
        $anotherUser = User::factory()->create();

        $this->actingAs($anotherUser);

        $testable = Livewire::test(UpdateBackupTaskForm::class, [
            'backupTask' => $this->data['backupTask'],
            'remoteServers' => $anotherUser->remoteServers,
        ]);

        $testable->set('label', 'Updated Label')
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
});

describe('validation rules', function (): void {
    test('backup task has required validation rules', function (): void {
        $testable = Livewire::test(UpdateBackupTaskForm::class, [
            'backupTask' => $this->data['backupTask'],
            'remoteServers' => RemoteServer::all(),
        ]);

        $testable->set('label', '')
            ->set('backupDestinationId', '')
            ->set('backupType', '')
            ->call('submit')
            ->assertHasErrors([
                'label' => 'required',
                'backupDestinationId' => 'required',
                'backupType' => 'required',
            ]);
    });

    test('the store path needs to be a valid unix path', function (): void {
        $testable = Livewire::test(UpdateBackupTaskForm::class, [
            'backupTask' => $this->data['backupTask'],
            'remoteServers' => RemoteServer::all(),
        ]);

        $testable->set('sourcePath', 'C:\var\www\html')
            ->call('submit')
            ->assertHasErrors([
                'sourcePath' => 'regex:/^\/[a-zA-Z0-9_\/]+$/', // Unix path
            ]);
    });

    test('the excluded database tables must be a valid comma separated list', function (): void {
        $testable = Livewire::test(UpdateBackupTaskForm::class, [
            'backupTask' => $this->data['backupTask'],
            'remoteServers' => RemoteServer::all(),
        ]);

        $testable->set('excludedDatabaseTables', 'table1, table2, table3')
            ->set('sourcePath', '/var/www/html')
            ->set('description', '')
            ->call('submit')
            ->assertHasErrors([
                'excludedDatabaseTables' => 'regex:/^([a-zA-Z0-9_]+,? ?)+$/', // Comma separated list
            ]);
    });
});

describe('time and timezone handling', function (): void {
    test('the time to run at is converted from the users timezone to UTC', function (): void {
        $this->data['user']->update(['timezone' => 'America/New_York']);

        $testable = Livewire::test(UpdateBackupTaskForm::class, [
            'backupTask' => $this->data['backupTask'],
            'remoteServers' => RemoteServer::all(),
        ]);

        $testable->set('timeToRun', '12:00') // 12:00 PM in America/New_York
            ->set('description', '')
            ->set('sourcePath', '/var/www/html')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('backup_tasks', [
            'time_to_run_at' => '16:00', // 4:00 PM in UTC
        ]);
    });

    test('a task cannot share the same time as another task on the same server', function (): void {
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

    test('a task retains its set time without validation errors', function (): void {
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
});

describe('tag handling', function (): void {
    test('users cannot set tags that do not belong them', function (): void {
        $tag = Tag::factory()->create();

        $testable = Livewire::test(UpdateBackupTaskForm::class, [
            'backupTask' => $this->data['backupTask'],
            'remoteServers' => RemoteServer::all(),
        ]);

        $testable->set('selectedTags', [$tag->id])
            ->call('submit')
            ->assertHasErrors([
                'selectedTags' => 'exists',
            ]);
    });

    test('users cannot set tags that do not exist', function (): void {
        $testable = Livewire::test(UpdateBackupTaskForm::class, [
            'backupTask' => $this->data['backupTask'],
            'remoteServers' => RemoteServer::all(),
        ]);

        $testable->set('selectedTags', [999])
            ->call('submit')
            ->assertHasErrors([
                'selectedTags' => 'exists',
            ]);
    });

    test('a user can update their already existing tags', function (): void {
        $user = User::factory()->create();

        $remoteServer = RemoteServer::factory()->create([
            'user_id' => $user->id,
        ]);

        $tag1 = Tag::factory()->create(['label' => 'Tag 1', 'user_id' => $user->id]);
        $tag2 = Tag::factory()->create(['label' => 'Tag 2', 'user_id' => $user->id]);
        $tag3 = Tag::factory()->create(['label' => 'Tag 3', 'user_id' => $user->id]);

        $backupTask = BackupTask::factory()->create([
            'user_id' => $user->id,
        ]);

        $backupTask->tags()->attach([$tag1->id, $tag2->id]);

        $this->actingAs($user);

        Livewire::test(UpdateBackupTaskForm::class, [
            'backupTask' => $backupTask,
            'remoteServers' => RemoteServer::all(),
            'availableTags' => $user->tags,
        ])
            ->set('remoteServerId', $remoteServer->id)
            ->set('selectedTags', [$tag3->id])
            ->set('sourcePath', '/var/www/html')
            ->set('description', '')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag3->id,
            'taggable_id' => $backupTask->id,
            'taggable_type' => BackupTask::class,
        ]);

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $tag1->id,
            'taggable_id' => $backupTask->id,
            'taggable_type' => BackupTask::class,
        ]);

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $tag2->id,
            'taggable_id' => $backupTask->id,
            'taggable_type' => BackupTask::class,
        ]);

        expect($backupTask->fresh()->tags)->toHaveCount(1)
            ->and($backupTask->fresh()->tags->first()->id)->toBe($tag3->id);
    });
});

describe('notification stream handling', function (): void {
    test('users cannot set notification streams that do not belong to them', function (): void {
        $notificationStream = NotificationStream::factory()->create();

        $testable = Livewire::test(UpdateBackupTaskForm::class, [
            'backupTask' => $this->data['backupTask'],
            'remoteServers' => RemoteServer::all(),
        ]);

        $testable->set('selectedStreams', [$notificationStream->id])
            ->call('submit')
            ->assertHasErrors([
                'selectedStreams' => 'exists',
            ]);
    });

    test('users cannot set streams that do not exist', function (): void {
        $testable = Livewire::test(UpdateBackupTaskForm::class, [
            'backupTask' => $this->data['backupTask'],
            'remoteServers' => RemoteServer::all(),
        ]);

        $testable->set('selectedStreams', [999])
            ->call('submit')
            ->assertHasErrors([
                'selectedStreams' => 'exists',
            ]);
    });

    test('a user can update their existing backup task notification streams', function (): void {
        $user = User::factory()->create();
        $remoteServer = RemoteServer::factory()->create(['user_id' => $user->id]);

        $streams = NotificationStream::factory()->email()->count(3)->create([
            'user_id' => $user->id,
        ])->each(function ($stream, $index): void {
            $stream->update(['label' => 'Stream ' . ($index + 1)]);
        });

        $backupTask = BackupTask::factory()->create(['user_id' => $user->id]);
        $backupTask->notificationStreams()->attach([$streams[0]->getAttribute('id'), $streams[1]->getAttribute('id')]);

        $this->actingAs($user);

        Livewire::test(UpdateBackupTaskForm::class, [
            'backupTask' => $backupTask,
            'remoteServers' => RemoteServer::all(),
            'availableStreams' => $user->notificationStreams,
        ])
            ->set('remoteServerId', $remoteServer->id)
            ->set('selectedStreams', [$streams[2]->getAttribute('id')])
            ->set('sourcePath', '/var/www/html')
            ->set('description', 'Updated description')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('backup_tasks', [
            'id' => $backupTask->id,
            'remote_server_id' => $remoteServer->id,
            'source_path' => '/var/www/html',
            'description' => 'Updated description',
        ]);

        $this->assertDatabaseHas('backup_task_notification_streams', [
            'backup_task_id' => $backupTask->id,
            'notification_stream_id' => $streams[2]->getAttribute('id'),
        ]);

        foreach ([$streams[0]->getAttribute('id'), $streams[1]->getAttribute('id')] as $detachedStreamId) {
            $this->assertDatabaseMissing('backup_task_notification_streams', [
                'backup_task_id' => $backupTask->id,
                'notification_stream_id' => $detachedStreamId,
            ]);
        }

        expect($backupTask->fresh()->notificationStreams)->toHaveCount(1)
            ->and($backupTask->fresh()->notificationStreams->first()->id)->toBe($streams[2]->getAttribute('id'));
    });
});
