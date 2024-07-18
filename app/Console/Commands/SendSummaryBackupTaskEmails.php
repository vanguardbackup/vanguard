<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\User\SummaryBackupMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class SendSummaryBackupTaskEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vanguard:dispatch-summary-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends dispatch summary emails to all opted-in users.';

    /**
     * The number of users emailed.
     */
    protected int $usersEmailed = 0;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->components->info('Beginning to gather data to send summary emails.');

        $users = User::optedInToReceiveSummaryEmails()->get();

        if ($users->isEmpty()) {
            $this->components->info('No users opted in to receive summary emails.');

            return;
        }

        $this->processUsers($users);

        $this->components->info("Sent summary emails to {$this->usersEmailed} opted-in users.");
    }

    /**
     * Process users and send summary emails.
     *
     * @param  Collection<int, User>  $users
     */
    private function processUsers(Collection $users): void
    {
        $dateRange = $this->getDateRange();

        foreach ($users as $user) {
            $data = $user->generateBackupSummaryData($dateRange);

            if (! empty($data['total_tasks'])) {
                $formattedData = [
                    'total_tasks' => $data['total_tasks'],
                    'successful_tasks' => $data['successful_tasks'],
                    'failed_tasks' => $data['failed_tasks'],
                    'success_rate' => $data['success_rate'],
                    'date_range' => [
                        'start' => $dateRange['start']->toDateString(),
                        'end' => $dateRange['end']->toDateString(),
                    ],
                ];
                Mail::to($user)->queue(new SummaryBackupMail($formattedData, $user));
                $this->usersEmailed++;
            }
        }
    }

    /**
     * Get the date range for the summary (Monday to Friday of the previous week).
     *
     * @return array<string, Carbon>
     */
    private function getDateRange(): array
    {
        $today = Carbon::today();
        $startDate = $today->copy()->subWeek()->startOfWeek()->startOfDay();
        $endDate = $startDate->copy()->endOfWeek()->subDays(2)->endOfDay(); // Friday

        return [
            'start' => $startDate instanceof Carbon ? $startDate : new Carbon($startDate),
            'end' => $endDate instanceof Carbon ? $endDate : new Carbon($endDate),
        ];
    }
}
