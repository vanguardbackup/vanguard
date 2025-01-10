<?php

declare(strict_types=1);

namespace App\Livewire\BackupTasks\Tables;

use App\Models\BackupTask;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Toaster;

/**
 * Manages the display and filtering of backup tasks in a table format.
 *
 * This component handles the rendering, pagination, and filtering of backup tasks for the authenticated user.
 */
class IndexTable extends Component
{
    use WithPagination;

    /** @var int The count of filtered backup tasks */
    public int $filteredCount = 0;

    /** @var int|null Selected tag ID for filtering */
    #[Url]
    public ?int $selectedTag = null;

    /** @var string|null The status to filter by */
    #[Url]
    public ?string $status = null;

    /** @var string The text input for label filtering */
    #[Url]
    public string $search = '';

    /** @var array<string> Available statuses for filtering */
    public array $statuses = ['running', 'ready'];

    /**
     * @var string[]
     */
    protected $listeners = ['refreshBackupTasksTable' => '$refresh'];

    /**
     * Render the backup tasks index table.
     *
     * Fetches and paginates filtered backup tasks for the authenticated user, including related data.
     */
    public function render(): View
    {
        $builder = $this->getFilteredQuery();

        $lengthAwarePaginator = $builder->paginate(Auth::user()?->getAttribute('pagination_count') ?? 15, pageName: 'backup-tasks');

        $this->filteredCount = $builder->count();

        $tags = Tag::where('user_id', Auth::id())->get();

        return view('livewire.backup-tasks.tables.index-table', [
            'backupTasks' => $lengthAwarePaginator,
            'tags' => $tags,
        ]);
    }

    /**
     * Reset all filters and clear URL parameters.
     */
    public function resetFilters(): void
    {
        $this->reset(['selectedTag', 'status', 'search']);
        $this->resetPage();
        $this->clearUrlParameters();

        Toaster::info('Cleared your Backup Tasks filter.');
    }

    /**
     * Get the filtered query for backup tasks.
     *
     * @return Builder<BackupTask>
     */
    private function getFilteredQuery(): Builder
    {
        $query = BackupTask::where('user_id', Auth::id())
            ->with(['remoteServer', 'backupDestination', 'tags'])
            ->withAggregate('latestLog', 'created_at');

        if ($this->selectedTag !== null) {
            $query->whereHas('tags', fn (Builder $builder) => $builder->where('id', $this->selectedTag));
        }

        if ($this->status !== null) {
            $query->where('status', $this->status);
        }

        if ($this->search !== '') {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");

            if ($driver === 'pgsql') {
                // Use ilike for PostgreSQL to make the search case-insensitive
                $query->where('label', 'ilike', "%{$this->search}%");
            } else {
                // TODO: Investigate approaches like 'ilike' in other engines to improve the experience.
                // Use like for other database engines
                $query->where('label', 'like', "%{$this->search}%");
            }
        }

        return $query->orderByRaw('favourited_at IS NULL')
            ->orderBy('favourited_at', 'desc')
            ->latest('id');
    }

    /**
     * Clear URL parameters related to filtering.
     */
    private function clearUrlParameters(): void
    {
        $this->dispatch('urlParametersCleared');
    }
}
