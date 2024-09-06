<?php

declare(strict_types=1);

use App\Livewire\Other\NewFeatureBanner;
use App\Models\User;
use App\Models\UserDismissal;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

it('shows the latest feature when available for authenticated user', function (): void {
    $user = User::factory()->create();
    $feature = [
        'title' => 'New Feature',
        'description' => 'This is a new feature',
        'version' => '1.0.0',
    ];
    Cache::put('latest_feature', $feature, now()->addHour());

    Livewire::actingAs($user)
        ->test(NewFeatureBanner::class)
        ->assertSee('New Feature')
        ->assertSee('This is a new feature')
        ->assertSee('Dismiss');
});

it('hides the banner when no feature is available', function (): void {
    $user = User::factory()->create();
    Cache::forget('latest_feature');

    Livewire::actingAs($user)
        ->test(NewFeatureBanner::class)
        ->assertDontSee('New Feature')
        ->assertDontSee('Dismiss');
});

it('dismisses the feature when clicked by authenticated user', function (): void {
    $user = User::factory()->create();
    $feature = [
        'title' => 'New Feature',
        'description' => 'This is a new feature',
        'version' => '1.0.0',
    ];
    Cache::put('latest_feature', $feature, now()->addHour());

    Livewire::actingAs($user)
        ->test(NewFeatureBanner::class)
        ->call('dismiss')
        ->assertDispatched('featureDismissed')
        ->assertDontSee('New Feature')
        ->assertDontSee('Dismiss');

    expect(UserDismissal::isDismissed($user->id, 'feature', '1.0.0'))->toBeTrue();
});

it('does not show dismissed feature on subsequent loads', function (): void {
    $user = User::factory()->create();
    $feature = [
        'title' => 'New Feature',
        'description' => 'This is a new feature',
        'version' => '1.0.0',
    ];
    Cache::put('latest_feature', $feature, now()->addHour());
    UserDismissal::dismiss($user->id, 'feature', '1.0.0');

    Livewire::actingAs($user)
        ->test(NewFeatureBanner::class)
        ->assertDontSee('New Feature')
        ->assertDontSee('Dismiss');
});

it('shows new feature even if previous version was dismissed', function (): void {
    $user = User::factory()->create();
    $feature = [
        'title' => 'Newer Feature',
        'description' => 'This is a newer feature',
        'version' => '1.1.0',
    ];
    Cache::put('latest_feature', $feature, now()->addHour());
    UserDismissal::dismiss($user->id, 'feature', '1.0.0');

    Livewire::actingAs($user)
        ->test(NewFeatureBanner::class)
        ->assertSee('Newer Feature')
        ->assertSee('This is a newer feature')
        ->assertSee('Dismiss');
});

it('logs a warning when cache contains unexpected data type', function (): void {
    $user = User::factory()->create();
    Cache::put('latest_feature', 'not an array', now()->addHour());

    Log::shouldReceive('warning')
        ->once()
        ->with('Unexpected data type for latest_feature in cache', ['type' => 'string']);

    Livewire::actingAs($user)->test(NewFeatureBanner::class);
});

it('handles missing version in feature data', function (): void {
    $user = User::factory()->create();
    $feature = [
        'title' => 'Incomplete Feature',
        'description' => 'This feature is missing a version',
    ];
    Cache::put('latest_feature', $feature, now()->addHour());

    Livewire::actingAs($user)
        ->test(NewFeatureBanner::class)
        ->assertSee('Incomplete Feature')
        ->assertSee('This feature is missing a version')
        ->assertSee('Dismiss')
        ->call('dismiss');

    expect(UserDismissal::isDismissed($user->id, 'feature', 'unknown'))->toBeTrue();
});

it('does not show banner when dismissed version is unknown', function (): void {
    $user = User::factory()->create();
    $feature = [
        'title' => 'Another Incomplete Feature',
        'description' => 'This feature is also missing a version',
    ];
    Cache::put('latest_feature', $feature, now()->addHour());
    UserDismissal::dismiss($user->id, 'feature', 'unknown');

    Livewire::actingAs($user)
        ->test(NewFeatureBanner::class)
        ->assertDontSee('Another Incomplete Feature')
        ->assertDontSee('This feature is also missing a version')
        ->assertDontSee('Dismiss');
});

it('does not show banner when user is not authenticated', function (): void {
    $feature = [
        'title' => 'New Feature',
        'description' => 'This is a new feature',
        'version' => '1.0.0',
    ];
    Cache::put('latest_feature', $feature, now()->addHour());

    Livewire::test(NewFeatureBanner::class)
        ->assertDontSee('New Feature')
        ->assertDontSee('This is a new feature')
        ->assertDontSee('Dismiss');
});

it('does not show banner when cache is empty', function (): void {
    $user = User::factory()->create();
    Cache::forget('latest_feature');

    Livewire::actingAs($user)
        ->test(NewFeatureBanner::class)
        ->assertDontSee('New Feature')
        ->assertDontSee('Dismiss');
});
