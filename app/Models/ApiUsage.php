<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ApiUsageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ApiUsage Model
 *
 * Represents API usage data for users.
 */
class ApiUsage extends Model
{
    /** @use HasFactory<ApiUsageFactory> */
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * Get chart data for overall API usage.
     *
     * @param  int  $userId  The ID of the user
     * @return array{labels: array<string>, datasets: array<array{label: string, data: array<int>}>}
     */
    public static function getChartData(int $userId): array
    {
        $endDate = Carbon::now()->endOfDay();
        $startDate = $endDate->copy()->subDays(29)->startOfDay();

        $dailyUsage = static::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        /* @phpstan-ignore-next-line */
        $filledData = self::fillMissingDates($dailyUsage, $startDate, $endDate);

        return [
            'labels' => $filledData->pluck('date')->toArray(),
            'datasets' => [
                [
                    'label' => 'API Usage Count',
                    'data' => $filledData->pluck('count')->toArray(),
                ],
            ],
        ];
    }

    /**
     * Get chart data for API usage breakdown by HTTP method.
     *
     * @param  int  $userId  The ID of the user
     * @return array{labels: array<string>, datasets: array<array{label: string, data: array<int>}>}
     */
    public static function getMethodBreakdownChartData(int $userId): array
    {
        $endDate = Carbon::now()->endOfDay();
        $startDate = $endDate->copy()->subDays(29)->startOfDay();

        $dailyUsage = static::query()
            ->select('method', DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('method', 'date')
            ->orderBy('date')
            ->get();

        $methods = $dailyUsage->pluck('method')->unique()->sort()->values()->toArray();
        $dates = self::generateDateRange($startDate, $endDate);

        $datasets = [];
        foreach ($methods as $method) {
            $data = array_fill_keys($dates, 0);
            foreach ($dailyUsage->where('method', $method) as $usage) {
                /* @phpstan-ignore-next-line */
                $data[$usage->date] = (int) $usage->count;
            }
            $datasets[] = [
                'label' => $method,
                'data' => array_values($data),
            ];
        }

        return [
            'labels' => $dates,
            'datasets' => $datasets,
        ];
    }

    /**
     * Fill in missing dates with zero counts.
     *
     * @param  Collection<string, object{count: int}>  $data  The existing data
     * @param  Carbon  $startDate  The start date of the range
     * @param  Carbon  $endDate  The end date of the range
     * @return Collection<int, array{date: string, count: int}>
     */
    private static function fillMissingDates(Collection $data, Carbon $startDate, Carbon $endDate): Collection
    {
        $filledData = collect();
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->toDateString();
            $count = $data->get($dateString)?->count ?? 0;
            $filledData->push(['date' => $dateString, 'count' => $count]);
            $currentDate->addDay();
        }

        return $filledData;
    }

    /**
     * Generate an array of dates within a given range.
     *
     * @param  Carbon  $startDate  The start date of the range
     * @param  Carbon  $endDate  The end date of the range
     * @return array<string>
     */
    private static function generateDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $dates = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->toDateString();
            $currentDate->addDay();
        }

        return $dates;
    }

    /**
     * Get the user associated with this API usage.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
