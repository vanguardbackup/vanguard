<?php

declare(strict_types=1);

use App\Livewire\Other\NewFeatureBanner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

it('shows the latest feature when available', function (): void {
    $feature = [
        'title' => 'New Feature',
        'description' => 'This is a new feature',
        'version' => '1.0.0',
    ];
    Cache::put('latest_feature', $feature, now()->addHour());

    Livewire::test(NewFeatureBanner::class)
        ->assertSee('New Feature')
        ->assertSee('This is a new feature')
        ->assertSee('Dismiss');
});

it('hides the banner when no feature is available', function (): void {
    Cache::forget('latest_feature');

    Livewire::test(NewFeatureBanner::class)
        ->assertDontSee('New Feature')
        ->assertDontSee('Dismiss');
});

it('dismisses the feature when clicked', function (): void {
    $feature = [
        'title' => 'New Feature',
        'description' => 'This is a new feature',
        'version' => '1.0.0',
    ];
    Cache::put('latest_feature', $feature, now()->addHour());

    Livewire::test(NewFeatureBanner::class)
        ->call('dismiss')
        ->assertDispatched('featureDismissed')
        ->assertDontSee('New Feature')
        ->assertDontSee('Dismiss');

    expect(Session::get('dismissed_feature_version'))->toBe('1.0.0');
});

it('does not show dismissed feature on subsequent loads', function (): void {
    $feature = [
        'title' => 'New Feature',
        'description' => 'This is a new feature',
        'version' => '1.0.0',
    ];
    Cache::put('latest_feature', $feature, now()->addHour());
    Session::put('dismissed_feature_version', '1.0.0');

    Livewire::test(NewFeatureBanner::class)
        ->assertDontSee('New Feature')
        ->assertDontSee('Dismiss');
});

it('shows new feature even if previous version was dismissed', function (): void {
    $feature = [
        'title' => 'Newer Feature',
        'description' => 'This is a newer feature',
        'version' => '1.1.0',
    ];
    Cache::put('latest_feature', $feature, now()->addHour());
    Session::put('dismissed_feature_version', '1.0.0');

    Livewire::test(NewFeatureBanner::class)
        ->assertSee('Newer Feature')
        ->assertSee('This is a newer feature')
        ->assertSee('Dismiss');
});

it('logs a warning when cache contains unexpected data type', function (): void {
    Cache::put('latest_feature', 'not an array', now()->addHour());

    Log::shouldReceive('warning')
        ->once()
        ->with('Unexpected data type for latest_feature in cache', ['type' => 'string']);

    Livewire::test(NewFeatureBanner::class);
});

it('handles missing version in feature data', function (): void {
    $feature = [
        'title' => 'Incomplete Feature',
        'description' => 'This feature is missing a version',
    ];
    Cache::put('latest_feature', $feature, now()->addHour());

    Livewire::test(NewFeatureBanner::class)
        ->assertSee('Incomplete Feature')
        ->assertSee('This feature is missing a version')
        ->assertSee('Dismiss');

    Livewire::test(NewFeatureBanner::class)
        ->call('dismiss');

    expect(Session::get('dismissed_feature_version'))->toBe('unknown');
});

it('does not show banner when dismissed version is unknown', function (): void {
    $feature = [
        'title' => 'Another Incomplete Feature',
        'description' => 'This feature is also missing a version',
    ];
    Cache::put('latest_feature', $feature, now()->addHour());
    Session::put('dismissed_feature_version', 'unknown');

    Livewire::test(NewFeatureBanner::class)
        ->assertDontSee('Another Incomplete Feature')
        ->assertDontSee('This feature is also missing a version')
        ->assertDontSee('Dismiss');
});

it('does not show banner when session is empty but cache is also empty', function (): void {
    Cache::forget('latest_feature');
    Session::forget('dismissed_feature_version');

    Livewire::test(NewFeatureBanner::class)
        ->assertDontSee('New Feature')
        ->assertDontSee('Dismiss');
});
