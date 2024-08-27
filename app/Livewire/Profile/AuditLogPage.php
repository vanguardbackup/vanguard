<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JsonException;
use Livewire\Component;
use Livewire\WithPagination;
use stdClass;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Toaster;

/**
 * Manages the display and interaction with user audit logs.
 *
 * This component handles pagination, searching, exporting, and detailed view of audit logs.
 */
class AuditLogPage extends Component
{
    use WithPagination;

    /** @var string The search query for filtering audit logs. */
    public string $search = '';

    /** @var int The number of items to display per page. */
    public int $perPage = 10;

    /** @var string The format for exporting audit logs (csv or json). */
    public string $exportFormat = 'csv';

    /** @var object|null The currently selected audit log for detailed view. */
    public ?object $selectedLog = null;

    /** @var array<string, mixed> The query string parameters to be tracked. */
    protected array $queryString = ['search' => ['except' => '']];

    /**
     * Reset the page when the search query is updated.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Select a specific audit log for detailed view.
     */
    public function selectLog(int $logId): void
    {
        $this->selectedLog = $this->getFilteredQuery()->where('audit_logs.id', $logId)->first();
        if ($this->selectedLog && property_exists($this->selectedLog, 'context') && is_string($this->selectedLog->context)) {
            $contextData = json_decode($this->selectedLog->context, true);
            if (is_array($contextData)) {
                $this->selectedLog->context = $this->filterContext($contextData);
            }
        }
        $this->dispatch('open-modal', 'audit-log-details');
    }

    /**
     * Clear the selected audit log and close the detail modal.
     */
    public function clearSelectedLog(): void
    {
        $this->selectedLog = null;
        $this->dispatch('close-modal', 'audit-log-details');
    }

    // Assuming Toaster is properly imported

    /**
     * Export the filtered audit logs in the specified format.
     */
    public function export(): StreamedResponse
    {
        try {
            $logs = $this->getFilteredQuery()->get();

            Toaster::info('Downloading your audit logs..');

            return response()->streamDownload(function () use ($logs): void {
                if ($this->exportFormat === 'csv') {
                    $this->exportToCsv($logs);
                } elseif ($this->exportFormat === 'json') {
                    $this->exportToJson($logs);
                }
            }, 'audit_logs.' . $this->exportFormat);
        } catch (Throwable $e) {
            Log::error('Error exporting audit logs: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => Auth::id(),
                'export_format' => $this->exportFormat,
            ]);

            Toaster::error('An error occurred while exporting audit logs. Please try again later.');

            return response()->streamDownload(function (): void {
                echo 'Export failed. Please view server logs if this issue persists.';
            }, 'export_failed.txt');
        }
    }

    /**
     * Check if the authenticated user has any audit logs.
     */
    public function hasAuditLogs(): bool
    {
        return DB::table('audit_logs')
            ->where('user_id', Auth::id())
            ->exists();
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        $auditLogs = $this->hasAuditLogs() ? $this->getFilteredQuery()->paginate($this->perPage) : collect();

        if ($this->hasAuditLogs() && $auditLogs instanceof LengthAwarePaginator) {
            $auditLogs->getCollection()->transform(function ($log) {
                $user = Auth::user();
                if ($user instanceof User) {
                    $log->gravatar_url = $user->gravatar(60);
                }

                return $log;
            });
        }

        return view('livewire.profile.audit-log-page', [
            'auditLogs' => $auditLogs,
        ])->layout('components.layouts.account-app');
    }

    /**
     * Export the logs to CSV format.
     *
     * @param  Collection<int, stdClass>  $logs
     */
    private function exportToCsv(Collection $logs): void
    {
        $handle = fopen('php://output', 'wb');
        if ($handle === false) {
            return;
        }
        fputcsv($handle, ['Action', 'User', 'Date']);

        foreach ($logs as $log) {
            $user = Auth::user();
            $timezone = $user ? $user->timezone : 'UTC';
            fputcsv($handle, [
                $log->message,
                $log->user_name,
                Carbon::parse($log->created_at)->timezone($timezone)->toDateTimeString(),
            ]);
        }

        fclose($handle);
    }

    /**
     * Export the logs to JSON format.
     *
     * @param  Collection<int, stdClass>  $logs
     *
     * @throws JsonException
     */
    private function exportToJson(Collection $logs): void
    {
        $user = Auth::user();
        $timezone = $user ? $user->timezone : 'UTC';
        $data = $logs->map(function ($log) use ($timezone): array {
            return [
                'action' => $log->message,
                'user' => $log->user_name,
                'date' => Carbon::parse($log->created_at)->timezone($timezone)->toDateTimeString(),
            ];
        });

        echo json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    /**
     * Parse the search query and apply filters to the query.
     */
    private function parseSearch(Builder $builder): Builder
    {
        $terms = preg_split('/\s+/', $this->search, -1, PREG_SPLIT_NO_EMPTY);

        if (is_array($terms)) {
            foreach ($terms as $term) {
                if (stripos($term, 'created:') === 0) {
                    $this->applyDateFilter($builder, substr($term, 8));
                } else {
                    $this->applyTermFilter($builder, $term);
                }
            }
        }

        return $builder;
    }

    /**
     * Apply date filter to the query.
     */
    private function applyDateFilter(Builder $builder, string $date): void
    {
        $builder->whereRaw('LOWER(DATE(audit_logs.created_at)) = LOWER(?)', [$date]);
    }

    /**
     * Apply term filter to the query.
     */
    private function applyTermFilter(Builder $builder, string $term): void
    {
        $builder->where(function (Builder $builder) use ($term): void {
            $builder->whereRaw('LOWER(audit_logs.message) LIKE LOWER(?)', ["%{$term}%"])
                ->orWhereRaw('LOWER(users.name) LIKE LOWER(?)', ["%{$term}%"]);
        });
    }

    /**
     * Get the filtered query for audit logs.
     */
    private function getFilteredQuery(): Builder
    {
        $query = DB::table('audit_logs')
            ->select('audit_logs.*', 'users.name as user_name', 'users.email as user_email')
            ->join('users', 'audit_logs.user_id', '=', 'users.id')
            ->where('audit_logs.user_id', Auth::id());

        if ($this->search !== '' && $this->search !== '0') {
            $query = $this->parseSearch($query);
        }

        return $query->latest('audit_logs.created_at');
    }

    /**
     * Filter out unnecessary fields from the context array and strip contents of encrypted fields.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function filterContext(array $context): array
    {
        $fieldsToFilter = ['created_at', 'updated_at', 'user_id', 'id', 'class'];
        $filteredContext = array_diff_key($context, array_flip($fieldsToFilter));

        $encryptedFields = $this->getEncryptedFields();

        foreach (array_keys($filteredContext) as $field) {
            if (in_array($field, $encryptedFields, true)) {
                $filteredContext[$field] = '[encrypted]';
            }
        }

        return $filteredContext;
    }

    /**
     * Get the list of encrypted fields.
     *
     * @return array<int, string>
     */
    private function getEncryptedFields(): array
    {
        return [
            'password',
            's3_access_key',
            's3_secret_key',
            's3_bucket_name',
            'custom_s3_endpoint',
            'database_password',
            'token',
            'isolated_username',
            'isolated_password',
        ];
    }
}
