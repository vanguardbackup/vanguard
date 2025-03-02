<?php

declare(strict_types=1);

use App\Models\Script;

it('filters only prescript scripts with prebackupScripts scope', function (): void {
    Script::factory()->count(2)->prescript()->create();
    Script::factory()->count(3)->postscript()->create();

    $prescripts = Script::prebackupScripts()->get();

    expect($prescripts)->toHaveCount(2);
    $prescripts->each(function ($script): void {
        expect($script->type)->toBe(Script::TYPE_PRESCRIPT);
    });
});

it('filters only postscript scripts with postbackupScripts scope', function (): void {
    Script::factory()->count(2)->prescript()->create();
    Script::factory()->count(3)->postscript()->create();

    $postscripts = Script::postbackupScripts()->get();

    expect($postscripts)->toHaveCount(3);
    $postscripts->each(function ($script): void {
        expect($script->type)->toBe(Script::TYPE_POSTSCRIPT);
    });
});

it('returns empty collection when no matching scripts exist', function (): void {
    Script::factory()->count(3)->postscript()->create();

    $prescripts = Script::prebackupScripts()->get();

    expect($prescripts)->toHaveCount(0)
        ->and($prescripts)->toBeEmpty();
});

it('can chain scopes with other query methods', function (): void {
    Script::factory()->prescript()->create(['label' => 'First prescript']);
    Script::factory()->prescript()->create(['label' => 'Second prescript']);
    Script::factory()->postscript()->create(['label' => 'First postscript']);

    $result = Script::prebackupScripts()
        ->where('label', 'First prescript')
        ->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->label)->toBe('First prescript')
        ->and($result->first()->type)->toBe(Script::TYPE_PRESCRIPT);
});
