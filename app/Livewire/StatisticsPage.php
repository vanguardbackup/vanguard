<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\ApiUsage;
use App\Models\BackupDestination;
use App\Models\BackupTask;
use App\Models\BackupTaskData;
use App\Models\RemoteServer;
use App\Models\User;
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
    /** @var array<int, string> Dates for the backup tasks chart */
    public array $backupDates = [];

    /** @var array<int, int> Counts of file backups for each date */
    public array $fileBackupCounts = [];

    /** @var array<int, int> Counts of database backups for each date */
    public array $databaseBackupCounts = [];

    /** @var array<int, string> Labels for the backup success rate chart */
    public array $backupSuccessRateLabels = [];

    /** @var array<int, float> Data for the backup success rate chart */
    public array $backupSuccessRateData = [];

    /** @var array<int, string> Labels for the average backup size chart */
    public array $averageBackupSizeLabels = [];

    /** @var array<int, float> Data for the average backup size chart */
    public array $averageBackupSizeData = [];

    /** @var array<int, string> Labels for the completion time chart */
    public array $completionTimeLabels = [];

    /** @var array<int, float> Data for the completion time chart */
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

    /** @var array<string> Labels for the API usage chart */
    public array $apiUsageLabels = [];

    /** @var array<int> Data for the API usage chart */
    public array $apiUsageData = [];

    /** @var array<string, mixed> API usage data grouped by method */
    public array $apiUsageMethodData = [];

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
        $this->loadApiUsageData();
        $this->loadApiUsageMethodData();
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
        $data = BackupTask::getBackupTasksData();
        $this->backupDates = $data['backupDates'];
        $this->fileBackupCounts = $data['fileBackupCounts'];
        $this->databaseBackupCounts = $data['databaseBackupCounts'];
    }

    /**
     * Load general statistics
     */
    private function loadStatistics(): void
    {
        $this->dataBackedUpInThePastSevenDays = Number::fileSize(
            (int) BackupTaskData::where('created_at', '>=', now()->subDays(7))->sum('size')
        );

        $this->dataBackedUpInThePastMonth = Number::fileSize(
            (int) BackupTaskData::where('created_at', '>=', now()->subMonth())->sum('size')
        );

        $this->dataBackedUpInTotal = Number::fileSize(
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
        $data = BackupTask::getBackupSuccessRateData();
        $this->backupSuccessRateLabels = array_map('strval', $data['labels']);
        $this->backupSuccessRateData = array_map('floatval', $data['data']);
    }

    /**
     * Load average backup size data
     */
    private function loadAverageBackupSizeData(): void
    {
        $data = BackupTask::getAverageBackupSizeData();
        $this->averageBackupSizeLabels = array_map('strval', $data['labels']);
        $this->averageBackupSizeData = array_map('floatval', $data['data']);
    }

    /**
     * Load completion time data
     */
    private function loadCompletionTimeData(): void
    {
        $data = BackupTask::getCompletionTimeData();
        $this->completionTimeLabels = array_map('strval', $data['labels']);
        $this->completionTimeData = array_map('floatval', $data['data']);
    }

    /**
     * Load API usage data for the past 30 days
     */
    private function loadApiUsageData(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $chartData = ApiUsage::getChartData($user->getAttribute('id'));
        $this->apiUsageLabels = $chartData['labels'];
        $this->apiUsageData = $chartData['datasets'][0]['data'];
    }

    /**
     * Load API usage data by request for the past 30 days
     */
    private function loadApiUsageMethodData(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->apiUsageMethodData = ApiUsage::getMethodBreakdownChartData($user->getAttribute('id'));
    }
}
