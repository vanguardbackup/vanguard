<?php

declare(strict_types=1);

use App\Models\ApiUsage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Carbon::setTestNow('2023-06-15 12:00:00');
});

it('returns correct chart data structure for overall usage', function (): void {
    $chartData = ApiUsage::getChartData($this->user->id);

    expect($chartData)->toHaveKeys(['labels', 'datasets'])
        ->and($chartData['datasets'])->toBeArray()->toHaveCount(1)
        ->and($chartData['datasets'][0])->toHaveKeys(['label', 'data']);
});

it('returns correct chart data structure for method breakdown', function (): void {
    $chartData = ApiUsage::getMethodBreakdownChartData($this->user->id);

    expect($chartData)
        ->toBeArray()
        ->toHaveKeys(['labels', 'datasets'])
        ->and($chartData['labels'])->toBeArray()
        ->and($chartData['datasets'])->toBeArray();
});

it('returns data for the last 30 days for both charts', function (): void {
    $overallChartData = ApiUsage::getChartData($this->user->id);
    $methodChartData = ApiUsage::getMethodBreakdownChartData($this->user->id);

    expect($overallChartData['labels'])->toHaveCount(30)
        ->and($methodChartData['labels'])->toHaveCount(30)
        ->and($overallChartData['labels'][0])->toBe('2023-05-17')
        ->and($overallChartData['labels'][29])->toBe('2023-06-15')
        ->and($methodChartData['labels'][0])->toBe('2023-05-17')
        ->and($methodChartData['labels'][29])->toBe('2023-06-15');
});

it('correctly counts API usage per day for overall usage', function (): void {
    ApiUsage::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'created_at' => '2023-06-10 12:00:00',
    ]);
    ApiUsage::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'created_at' => '2023-06-05 12:00:00',
    ]);

    $chartData = ApiUsage::getChartData($this->user->id);

    $june5Index = array_search('2023-06-05', $chartData['labels'], true);
    $june10Index = array_search('2023-06-10', $chartData['labels'], true);

    expect($chartData['datasets'][0]['data'][$june5Index])->toBe(2)
        ->and($chartData['datasets'][0]['data'][$june10Index])->toBe(3);

    foreach ($chartData['datasets'][0]['data'] as $index => $value) {
        if ($index !== $june5Index && $index !== $june10Index) {
            expect($value)->toBe(0);
        }
    }
});

it('correctly groups API usage by method', function (): void {
    ApiUsage::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'method' => 'GET',
        'created_at' => '2023-06-10 12:00:00',
    ]);
    ApiUsage::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'method' => 'POST',
        'created_at' => '2023-06-10 12:00:00',
    ]);
    ApiUsage::factory()->create([
        'user_id' => $this->user->id,
        'method' => 'PUT',
        'created_at' => '2023-06-05 12:00:00',
    ]);

    $chartData = ApiUsage::getMethodBreakdownChartData($this->user->id);

    expect($chartData['datasets'])->toHaveCount(3)
        ->and($chartData['datasets'][0]['label'])->toBe('GET')
        ->and($chartData['datasets'][1]['label'])->toBe('POST')
        ->and($chartData['datasets'][2]['label'])->toBe('PUT');

    $getDataset = collect($chartData['datasets'])->firstWhere('label', 'GET');
    $postDataset = collect($chartData['datasets'])->firstWhere('label', 'POST');
    $putDataset = collect($chartData['datasets'])->firstWhere('label', 'PUT');

    $june10Index = array_search('2023-06-10', $chartData['labels'], true);
    $june5Index = array_search('2023-06-05', $chartData['labels'], true);

    expect($getDataset['data'][$june10Index])->toBe(3)
        ->and($postDataset['data'][$june10Index])->toBe(2)
        ->and($putDataset['data'][$june5Index])->toBe(1);
});

it('returns zero for days with no API usage in overall chart', function (): void {
    $chartData = ApiUsage::getChartData($this->user->id);

    expect($chartData['datasets'][0]['data'])->each(
        fn ($value) => $value->toBe(0)
    );
});

it('returns empty datasets for method breakdown when no usage', function (): void {
    $chartData = ApiUsage::getMethodBreakdownChartData($this->user->id);

    expect($chartData['datasets'])->toBeEmpty();
});

it('only includes data for the specified user in both charts', function (): void {
    $otherUser = User::factory()->create();
    ApiUsage::factory()->count(5)->create([
        'user_id' => $otherUser->id,
        'created_at' => now()->subDays(5),
    ]);

    $overallChartData = ApiUsage::getChartData($this->user->id);
    $methodChartData = ApiUsage::getMethodBreakdownChartData($this->user->id);

    expect($overallChartData['datasets'][0]['data'])->each(
        fn ($value) => $value->toBe(0)
    );

    expect($methodChartData['datasets'])->toBeEmpty();
});

it('handles leap years correctly in both charts', function (): void {
    $this->travelTo(Carbon::create(2024, 2, 29));

    $overallChartData = ApiUsage::getChartData($this->user->id);
    $methodChartData = ApiUsage::getMethodBreakdownChartData($this->user->id);

    expect($overallChartData['labels'])->toHaveCount(30)
        ->and($overallChartData['labels'][0])->toBe('2024-01-31')
        ->and($overallChartData['labels'][29])->toBe('2024-02-29');

    expect($methodChartData['labels'])->toHaveCount(30)
        ->and($methodChartData['labels'][0])->toBe('2024-01-31')
        ->and($methodChartData['labels'][29])->toBe('2024-02-29');
});
