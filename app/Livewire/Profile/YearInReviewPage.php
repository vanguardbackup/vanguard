<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\BackupTask;
use App\Models\BackupTaskData;
use App\Models\BackupTaskLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;
use Illuminate\View\View;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

/**
 * Manages the user's year in review page in the profile section.
 * Renders the view using the 'account-app' layout.
 */
class YearInReviewPage extends Component
{
    public bool $hasYearInReviewData = true;
    public bool $currentlyGeneratingYearInReview = false;

    public function render(): View
    {
        if (! year_in_review_active()) {
            $this->redirectAwayIfInactive();
        }

        $this->checkIfYearInReviewDataHasBeenGenerated();

        return view('livewire.profile.year-in-review-page', [
            'yearInReviewData' => $this->getYearInReviewData(),
        ])
            ->layout('components.layouts.account-app');
    }

    /**
     *  Collates the data for the user and stores it in the cache for rendering in the view.
     */
    public function generateYearInReviewData(): void
    {
        // Shows the loading screen for a little bit!
        $this->currentlyGeneratingYearInReview = true;

        $currentYear = Carbon::now()->year;

        $backupTasksCreatedThisYear = BackupTask::where('user_id', Auth::id())
            ->whereYear('created_at', $currentYear)
            ->count();

        $backupTasksRanThisYear = BackupTaskLog::whereHas('backupTask', function ($query): void {
            $query->where('user_id', Auth::id());
        })
            ->whereYear('created_at', $currentYear)
            ->count();

        $successfulBackupTasksRanThisYear = BackupTaskLog::whereNotNull('successful_at')
            ->whereHas('backupTask', function ($query): void {
                $query->where('user_id', Auth::id());
            })
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $amountOfDataBackedUpInCurrentYear = Number::fileSize(
            (int) BackupTaskData::whereYear('created_at', Carbon::now()->year)->sum('size')
        );

        $yearInReviewData = [
            'backup_tasks_created' => $backupTasksCreatedThisYear,
            'backup_tasks_ran' => $backupTasksRanThisYear,
            'successful_backup_tasks' => $successfulBackupTasksRanThisYear,
            'data_amount' => $amountOfDataBackedUpInCurrentYear,
        ];

        /** @var User $user */
        $user = Auth::user();

        Cache::put('year_in_review_data_' . $user->getAttribute('id'), $yearInReviewData, 36000000000000); // Cache for ages!

        $this->hasYearInReviewData = true;

        $this->currentlyGeneratingYearInReview = false;
    }

    /**
     * Sends the user away if the Year in Review system is inactive.
     */
    private function redirectAwayIfInactive(): void
    {
        Toaster::error('Sorry! You cannot view your Year in Review at this time. Please check back later.');
        redirect()->route('profile');
    }

    private function checkIfYearInReviewDataHasBeenGenerated(): void
    {
        /** @var User $user */
        $user = Auth::user();

        // Check cache for the user's year in review data
        $cacheData = Cache::get('year_in_review_data_' . $user->getAttribute('id'));

        // If no data in cache, the user does not have data ready
        $this->hasYearInReviewData = (bool) $cacheData;
    }

    /**
     * Returns all the necessary year in review data and stores it in the cache.
     *
     * @return string[] Array of strings
     */
    private function getYearInReviewData(): array
    {
        /** @var User $user */
        $user = Auth::user();

        if ($this->hasYearInReviewData) {
            // Pull the cached year-in-review data
            return Cache::get('year_in_review_data_' . $user->getAttribute('id'));
        }

        return [];
    }
}
