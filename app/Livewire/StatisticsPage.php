<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\BackupTaskData;
use App\Models\BackupTaskLog;
use App\Models\RemoteServer;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Illuminate\View\View;
use Livewire\Component;

/**
 * StatisticsPage Livewire Component
 *
 * This component handles the statistics page, loading and preparing various
 * backup-related statistics for display.
 */
class StatisticsPage extends Component
{
    /** @var array<string> Dates for the backup tasks chart */
    public array $backupDates = [];

    /** @var array<int> Counts of file backups for each date */
    public array $fileBackupCounts = [];

    /** @var array<int> Counts of database backups for each date */
    public array $databaseBackupCounts = [];

    /** @var array<string> Labels for the backup success rate chart */
    public array $backupSuccessRateLabels = [];

    /** @var array<float> Data for the backup success rate chart */
    public array $backupSuccessRateData = [];

    /** @var array<string> Labels for the average backup size chart */
    public array $averageBackupSizeLabels = [];

    /** @var array<float> Data for the average backup size chart */
    public array $averageBackupSizeData = [];

    /** @var array<string> Labels for the completion time chart */
    public array $completionTimeLabels = [];

    /** @var array<float> Data for the completion time chart */
    public array $completionTimeData = [];

    /** @var string Total data backed up in the past seven days */
    public string $dataBackedUpInThePastSevenDays;

    /** @var string Total data backed up in the past month */
    public string $dataBackedUpInThePastMonth;

    /** @var string Total data backed up overall */
    public string $dataBackedUpInTotal;

    /** @var int Number of linked servers */
    public int $linkedServers;

    /** @var int Number of linked backup destinations */
    public int $linkedBackupDestinations;

    /** @var int Number of active backup tasks */
    public int $activeBackupTasks;

    /** @var int Number of paused backup tasks */
    public int $pausedBackupTasks;

    /**
     * Initialize the component state
     */
    public function mount(): void
    {
        $this->loadBackupTasksData();
        $this->loadStatistics();
        $this->loadAverageBackupSizeData();
        $this->loadBackupSuccessRateData();
        $this->loadCompletionTimeData();
    }

    /**
     * Render the component
     */
    public function render(): View
    {
        return view('livewire.statistics-page');
    }

    /**
     * Load backup tasks data for the past 90 days
     */
    private function loadBackupTasksData(): void
    {
        $startDate = now()->subDays(89);
        $endDate = now();

        $backupTasks = BackupTask::selectRaw('DATE(created_at) as date, type, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();

        $dates = Collection::make($startDate->daysUntil($endDate)->toArray())
            ->map(fn (CarbonInterface $date, $key): string => $date->format('Y-m-d'));

        $fileBackups = $databaseBackups = array_fill_keys($dates->toArray(), 0);

        foreach ($backupTasks as $backupTask) {
            $date = $backupTask['date'];
            $count = (int) $backupTask['count'];
            if ($backupTask['type'] === 'Files') {
                $fileBackups[$date] = $count;
            } else {
                $databaseBackups[$date] = $count;
            }
        }

        $this->backupDates = $dates->values()->toArray();
        $this->fileBackupCounts = array_values($fileBackups);
        $this->databaseBackupCounts = array_values($databaseBackups);
    }

    /**
     * Load general statistics
     */
    private function loadStatistics(): void
    {
        $this->dataBackedUpInThePastSevenDays = $this->formatFileSize(
            (int) BackupTaskData::where('created_at', '>=', now()->subDays(7))->sum('size')
        );

        $this->dataBackedUpInThePastMonth = $this->formatFileSize(
            (int) BackupTaskData::where('created_at', '>=', now()->subMonth())->sum('size')
        );

        $this->dataBackedUpInTotal = $this->formatFileSize(
            (int) BackupTaskData::sum('size')
        );

        $this->linkedServers = RemoteServer::whereUserId(Auth::id())->count();
        $this->linkedBackupDestinations = BackupDestination::whereUserId(Auth::id())->count();
        $this->activeBackupTasks = BackupTask::whereUserId(Auth::id())->whereNull('paused_at')->count();
        $this->pausedBackupTasks = BackupTask::whereUserId(Auth::id())->whereNotNull('paused_at')->count();
    }

    /**
     * Load backup success rate data for the past 6 months
     */
    private function loadBackupSuccessRateData(): void
    {
        $startDate = now()->startOfMonth()->subMonths(5);
        $endDate = now()->endOfMonth();

        $backupLogs = BackupTaskLog::selectRaw("DATE_TRUNC('month', created_at)::date as month")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN successful_at IS NOT NULL THEN 1 ELSE 0 END) as successful')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $this->backupSuccessRateLabels = $backupLogs->pluck('month')->map(fn ($date): string => Carbon::parse($date)->format('Y-m'))->toArray();
        $this->backupSuccessRateData = $backupLogs->map(function ($log): float|int {
            $total = (int) ($log['total'] ?? 0);
            $successful = (int) ($log['successful'] ?? 0);

            return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
        })->toArray();
    }

    /**
     * Load average backup size data
     */
    private function loadAverageBackupSizeData(): void
    {
        $backupSizes = BackupTask::join('backup_task_data', 'backup_tasks.id', '=', 'backup_task_data.backup_task_id')
            ->join('backup_task_logs', 'backup_tasks.id', '=', 'backup_task_logs.backup_task_id')
            ->select('backup_tasks.type')
            ->selectRaw('AVG(backup_task_data.size) as average_size')
            ->whereNotNull('backup_task_logs.successful_at')
            ->groupBy('backup_tasks.type')
            ->get();

        $this->averageBackupSizeLabels = $backupSizes->pluck('type')->toArray();
        $this->averageBackupSizeData = $backupSizes->pluck('average_size')
            ->map(fn ($size): string => $this->formatFileSize((int) $size))
            ->toArray();
    }

    private function loadCompletionTimeData(): void
    {
        $startDate = now()->subMonths(3);
        $endDate = now();

        $completionTimes = BackupTaskData::join('backup_task_logs', 'backup_task_data.backup_task_id', '=', 'backup_task_logs.backup_task_id')
            ->selectRaw('DATE(backup_task_logs.created_at) as date')
            ->selectRaw("
            AVG(
                CASE
                    WHEN backup_task_data.duration ~ '^\\d+$' THEN backup_task_data.duration::integer
                    WHEN backup_task_data.duration ~ '^(\\d+):(\\d+):(\\d+)$' THEN
                        (SUBSTRING(backup_task_data.duration FROM '^(\\d+)'))::integer * 3600 +
                        (SUBSTRING(backup_task_data.duration FROM '^\\d+:(\\d+)'))::integer * 60 +
                        (SUBSTRING(backup_task_data.duration FROM ':(\\d+)$'))::integer
                    ELSE 0
                END
            ) as avg_duration
        ")
            ->whereBetween('backup_task_logs.created_at', [$startDate, $endDate])
            ->whereNotNull('backup_task_logs.successful_at')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $this->completionTimeLabels = $completionTimes->pluck('date')->toArray();
        $this->completionTimeData = $completionTimes->pluck('avg_duration')
            ->map(fn ($duration): float => round($duration / 60, 2))
            ->toArray();
    }

    /**
     * Format file size using the Number facade
     */
    private function formatFileSize(int $bytes): string
    {
        return Number::fileSize($bytes);
    }
}
