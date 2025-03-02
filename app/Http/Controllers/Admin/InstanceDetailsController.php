<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\View\View;
use PDO;

/**
 * Controller for displaying instance details.
 *
 * Provides information about the server, Laravel installation, and application state.
 */
class InstanceDetailsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            abort(404);
        }

        $adminEmails = $this->getAdminEmailAddresses();

        $details = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'horizon_running' => $this->isHorizonRunning(),
            'pulse_running' => $this->isPulseRunning(),
            'domain' => $request->getHost(),
            'admin_count' => count($adminEmails),
            'admin_email_addresses' => $adminEmails,
            'smtp_config' => $this->getSmtpConfig(),
            'user_count' => $this->getUserCount(),
            'backup_task_count' => $this->getBackupTaskCount(),
            'database_type' => DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME),
            'database_version' => DB::connection()->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION),
            'vanguard_version' => obtain_vanguard_version(),
        ];

        return view('admin.instance-details', ['details' => $details]);
    }

    /**
     * Check if Laravel Horizon is running.
     */
    private function isHorizonRunning(): bool
    {
        $result = Process::run('ps aux | grep "artisan horizon" | grep -v grep');

        return $result->successful() && !empty($result->output());
    }

    /**
     * Check if Laravel Pulse is running.
     */
    private function isPulseRunning(): bool
    {
        return Cache::has('laravel:pulse:measurements');
    }

    /**
     * Get admin email addresses from config.
     *
     * @return array<int, string>
     */
    private function getAdminEmailAddresses(): array
    {
        return Config::get('auth.admin_email_addresses', []);
    }

    /**
     * Get SMTP configuration details.
     *
     * @return array<string, string|int|null>
     */
    private function getSmtpConfig(): array
    {
        return [
            'host' => Config::get('mail.mailers.smtp.host'),
            'port' => Config::get('mail.mailers.smtp.port'),
            'encryption' => Config::get('mail.mailers.smtp.encryption'),
        ];
    }

    /**
     * Get the total count of users.
     */
    private function getUserCount(): int
    {
        return User::count();
    }

    /**
     * Get the total count of backup tasks.
     */
    private function getBackupTaskCount(): int
    {
        return BackupTask::count();
    }
}
