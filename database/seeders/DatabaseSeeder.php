<?php

namespace Database\Seeders;

use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\BackupTaskData;
use App\Models\BackupTaskLog;
use App\Models\RemoteServer;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Arr;

class DatabaseSeeder extends Seeder
{
    private const array SERVER_NAMES = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Eta', 'Theta'];
    private const array BACKUP_TASKS = ['Daily Backup', 'Weekly Archive', 'Monthly Snapshot', 'Database Backup', 'User Files Backup', 'System Config Backup'];
    private const array FREQUENCIES = ['daily', 'weekly', null];
    private const array STATUSES = ['ready'];

    public function run(): void
    {
        $user = $this->createUser();
        $this->createTags($user);
        $servers = $this->createServers($user);
        $backupDestination = $this->createBackupDestination($user);
        $backupTasks = $this->createBackupTasks($user, $servers, $backupDestination);
        $this->createBackupTaskData($backupTasks);
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    private function createTags(User $user): void
    {
        Tag::factory()->count(random_int(3, 8))->create([
            'user_id' => $user->id,
        ]);
    }

    private function createServers(User $user): array
    {
        $servers = [];
        foreach (Arr::random(self::SERVER_NAMES, random_int(3, 5)) as $name) {
            $servers[] = RemoteServer::factory()->create([
                'user_id' => $user->id,
                'label' => $name,
                'ip_address' => $this->generateRandomIp(),
                'port' => random_int(22, 8080),
                'username' => Arr::random(['vanguard', 'admin', 'root']),
                'connectivity_status' => Arr::random([RemoteServer::STATUS_ONLINE, RemoteServer::STATUS_OFFLINE]),
                'database_password' => random_int(1, 2) === 1 ? Crypt::encryptString('password') : null,
            ]);
        }
        return $servers;
    }

    private function createBackupDestination(User $user): BackupDestination
    {
        return BackupDestination::factory()->create([
            'user_id' => $user->id,
            'label' => 'S3 Backup',
            'type' => 'custom_s3',
            'custom_s3_endpoint' => 'https://s3.example.com',
            'custom_s3_region' => Arr::random(['eu-west-1', 'us-east-1', 'ap-southeast-2']),
            's3_access_key' => $this->generateRandomString(20),
            's3_secret_key' => $this->generateRandomString(40),
            's3_bucket_name' => 'backups-' . strtolower($this->generateRandomString(8)),
        ]);
    }

    private function createBackupTasks(User $user, array $servers, BackupDestination $backupDestination): array
    {
        $tasks = [];
        foreach (Arr::random(self::BACKUP_TASKS, random_int(3, 6)) as $taskName) {
            $frequency = Arr::random(self::FREQUENCIES);
            $tasks[] = BackupTask::factory()->create([
                'user_id' => $user->id,
                'backup_destination_id' => $backupDestination->id,
                'remote_server_id' => Arr::random($servers)->id,
                'label' => $taskName,
                'description' => "This is a {$taskName} task",
                'source_path' => $this->generateRandomPath(),
                'status' => Arr::random(self::STATUSES),
                'frequency' => $frequency,
                'time_to_run_at' => $frequency ? $this->generateRandomTime() : null,
                'custom_cron_expression' => $frequency === null ? $this->generateRandomCron() : null,
                'last_run_at' => $this->generateRandomDate(),
                'type' => random_int(1, 5) === 1 ? BackupTask::TYPE_DATABASE : BackupTask::TYPE_FILES,
            ]);
        }
        return $tasks;
    }

    private function createBackupTaskData(array $backupTasks): void
    {
        $startDate = Carbon::create(now()->year, 1, 1);
        $endDate = Carbon::create(now()->year, 6, 30);

        foreach ($backupTasks as $task) {
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                if (random_int(1, 100) <= 90) { // 90% chance of creating an entry for each day
                    BackupTaskData::create([
                        'backup_task_id' => $task->id,
                        'size' => $this->generateRandomSize(),
                        'duration' => $this->generateRandomDuration(),
                        'created_at' => $currentDate->copy()->addHours(random_int(0, 23))->addMinutes(random_int(0, 59)),
                    ]);

                    if (random_int(1, 100) <= 20) { // 20% chance of creating a log entry
                        BackupTaskLog::create([
                            'backup_task_id' => $task->id,
                            'output' => Arr::random([
                                'Backup task completed successfully',
                                'Backup task failed: connection timeout',
                                'Partial backup completed: some files were inaccessible',
                                'Backup aborted: insufficient storage space',
                            ]),
                            'created_at' => $currentDate,
                        ]);
                    }
                }
                $currentDate->addDay();
            }
        }
    }

    private function generateRandomIp(): string
    {
        return implode('.', [
            random_int(1, 255),
            random_int(0, 255),
            random_int(0, 255),
            random_int(0, 255)
        ]);
    }

    private function generateRandomString(int $length): string
    {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }

    private function generateRandomPath(): string
    {
        $dirs = ['var', 'www', 'html', 'public', 'storage', 'app', 'data'];
        $path = '/';
        $depth = random_int(1, 4);
        for ($i = 0; $i < $depth; $i++) {
            $path .= Arr::random($dirs) . '/';
        }
        return rtrim($path, '/');
    }

    private function generateRandomTime(): string
    {
        return sprintf("%02d:%02d", random_int(0, 23), random_int(0, 59));
    }

    private function generateRandomCron(): string
    {
        return implode(' ', [
            random_int(0, 59), // minute
            random_int(0, 23), // hour
            '*', // day of month
            '*', // month
            random_int(0, 6)  // day of week
        ]);
    }

    private function generateRandomDate(): string
    {
        return Carbon::now()->subDays(random_int(0, 30))->format('Y-m-d H:i:s');
    }

    private function generateRandomSize(): int
    {
        return random_int(500, 5000); // Random size between 500 MB and 5 GB (in MB)
    }

    private function generateRandomDuration(): int
    {
        return random_int(600, 7200); // Random duration between 10 minutes and 2 hours (in seconds)
    }
}
