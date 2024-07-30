<?php

declare(strict_types=1);

use App\Livewire\Other\NewFeatureBanner;
use Illuminate\Support\Facades\Cache;

it('shows the latest feature when available', function (): void {
    $feature = ['title' => 'New Feature', 'description' => 'This is a new feature'];
    Cache::put('latest_feature', $feature, now()->addHour());

    $component = Livewire::test(NewFeatureBanner::class);

    $component->assertSee('New Feature')
        ->assertSee('This is a new feature')
        ->assertSee('Dismiss');
});

it('hides the banner when no feature is available', function (): void {
    Cache::forget('latest_feature');

    $component = Livewire::test(NewFeatureBanner::class);

    $component->assertDontSee('New Feature')
        ->assertDontSee('Dismiss');
});

it('dismisses the feature when clicked', function (): void {
    $feature = ['title' => 'New Feature', 'description' => 'This is a new feature'];
    Cache::put('latest_feature', $feature, now()->addHour());

    $component = Livewire::test(NewFeatureBanner::class);

    $component->call('dismiss')
        ->assertDispatched('featureDismissed')
        ->assertDontSee('New Feature')
        ->assertDontSee('Dismiss');
});
