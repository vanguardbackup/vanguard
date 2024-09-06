<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserDismissal;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UserDismissal', function (): void {
    it('can check if an item is dismissed', function (): void {
        $user = User::factory()->create();
        UserDismissal::factory()->create([
            'user_id' => $user->id,
            'dismissable_type' => 'feature',
            'dismissable_id' => 'test_feature',
        ]);

        expect(UserDismissal::isDismissed($user->id, 'feature', 'test_feature'))->toBeTrue()
            ->and(UserDismissal::isDismissed($user->id, 'feature', 'non_existent_feature'))->toBeFalse();
    });

    it('can dismiss an item', function (): void {
        $user = User::factory()->create();

        $userDismissal = UserDismissal::dismiss($user->id, 'guide', 'intro_guide');

        expect($userDismissal)->toBeInstanceOf(UserDismissal::class)
            ->and($userDismissal->getAttribute('user_id'))->toBe($user->id)
            ->and($userDismissal->getAttribute('dismissable_type'))->toBe('guide')
            ->and($userDismissal->getAttribute('dismissable_id'))->toBe('intro_guide')
            ->and($userDismissal->getAttribute('dismissed_at'))->toBeInstanceOf(Carbon::class);
    });

    it('can scope query to a specific type', function (): void {
        UserDismissal::factory()->count(3)->create(['dismissable_type' => 'feature']);
        UserDismissal::factory()->count(2)->create(['dismissable_type' => 'guide']);

        $featureDismissals = UserDismissal::ofType('feature')->get();
        $guideDismissals = UserDismissal::ofType('guide')->get();

        expect($featureDismissals)->toHaveCount(3)
            ->and($guideDismissals)->toHaveCount(2);
    });

    it('prevents duplicate dismissals', function (): void {
        $user = User::factory()->create();

        UserDismissal::dismiss($user->id, 'feature', 'test_feature');

        // Attempting to dismiss the same item again
        $this->expectException(QueryException::class);
        UserDismissal::dismiss($user->id, 'feature', 'test_feature');
    });

    it('associates dismissal with correct user', function (): void {
        $user = User::factory()->create();
        $userDismissal = UserDismissal::dismiss($user->id, 'feature', 'test_feature');

        expect($userDismissal->getAttribute('user'))->toBeInstanceOf(User::class)
            ->and($userDismissal->getAttribute('user')->id)->toBe($user->id);
    });
});
