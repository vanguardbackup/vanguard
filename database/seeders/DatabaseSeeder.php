<?php

namespace Database\Seeders;

use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use App\Models\RemoteServer;
use App\Models\Tag;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Tag::factory()->count(5)->create([
            'user_id' => $user->id,
        ]);

        $remoteServer = RemoteServer::factory([
            'user_id' => $user->id,
            'label' => 'Alpha',
            'ip_address' => '231.33.70.31',
            'port' => 22,
            'username' => 'vanguard',
            'connectivity_status' => RemoteServer::STATUS_ONLINE,
        ])->create();

        $remoteServerTwo = RemoteServer::factory([
            'user_id' => $user->id,
            'label' => 'Beta',
            'ip_address' => '142.98.154.47',
            'port' => 22,
            'username' => 'vanguard',
            'connectivity_status' => RemoteServer::STATUS_OFFLINE,
            'database_password' => Crypt::encryptString('password'),
        ])->create();

        $remoteServerThree = RemoteServer::factory([
            'user_id' => $user->id,
            'label' => 'Delta',
            'ip_address' => '64.96.13.221',
            'port' => 22,
            'username' => 'vanguard',
            'connectivity_status' => RemoteServer::STATUS_OFFLINE,
        ])->create();

        $backupDestination = BackupDestination::factory([
            'user_id' => $user->id,
            'label' => 'S3 Backup',
            'type' => 'custom_s3',
            'custom_s3_endpoint' => 'https://s3.example.com',
            'custom_s3_region' => 'eu-west-1',
            's3_access_key' => '123456',
            's3_secret_key' => 'abcdef',
            's3_bucket_name' => 'backups',
        ])->create();

        $backupTask = BackupTask::factory([
            'user_id' => $user->id,
            'backup_destination_id' => $backupDestination->id,
            'remote_server_id' => $remoteServer->id,
            'label' => 'Test Task',
            'description' => 'This is a test backup task',
            'source_path' => '/vanguard/example.com',
            'status' => 'ready',
            'frequency' => 'daily',
            'time_to_run_at' => '05:30',
            'custom_cron_expression' => null,
            'last_run_at' => '2024-06-04 10:31:33',
        ])->create();

        BackupTask::factory([
            'user_id' => $user->id,
            'backup_destination_id' => $backupDestination->id,
            'remote_server_id' => $remoteServer->id,
            'label' => 'Backup Photos',
            'description' => 'This is a test backup task',
            'source_path' => '/vanguard/example.com',
            'status' => 'ready',
            'frequency' => null,
            'time_to_run_at' => null,
            'custom_cron_expression' => '0 5 * * *',
            'last_run_at' => '2024-06-04 08:31:33',
        ])->create();

        BackupTask::factory([
            'user_id' => $user->id,
            'backup_destination_id' => $backupDestination->id,
            'remote_server_id' => $remoteServerTwo->id,
            'label' => 'Backup Database',
            'description' => 'This is a test backup task',
            'source_path' => null,
            'status' => 'ready',
            'frequency' => 'daily',
            'time_to_run_at' => '05:30',
            'custom_cron_expression' => null,
            'last_run_at' => '2024-06-04 10:31:33',
            'type' => BackupTask::TYPE_DATABASE,
        ])->create();

        BackupTaskLog::create([
            'backup_task_id' => $backupTask->id,
            'output' => 'Backup task completed successfully',
        ]);
    }
}
