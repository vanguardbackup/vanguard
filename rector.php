<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\CustomRules\ReplaceModelAttributesRector;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Rector\ClassMethod\AddGenericReturnTypeToRelationsRector;
use RectorLaravel\Rector\ClassMethod\MigrateToSimplifiedAttributeRector;
use RectorLaravel\Rector\Expr\AppEnvironmentComparisonToParameterRector;
use RectorLaravel\Rector\MethodCall\EloquentWhereTypeHintClosureParameterRector;
use RectorLaravel\Rector\MethodCall\UseComponentPropertyWithinCommandsRector;
use RectorLaravel\Rector\MethodCall\ValidationRuleArrayStringValueToArrayRector;
use RectorLaravel\Set\LaravelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->rules([
        // Custom
        ReplaceModelAttributesRector::class,

        // Laravel Rules
        AddGenericReturnTypeToRelationsRector::class,
        AppEnvironmentComparisonToParameterRector::class,
        EloquentWhereTypeHintClosureParameterRector::class,
        MigrateToSimplifiedAttributeRector::class,
        UseComponentPropertyWithinCommandsRector::class,
        ValidationRuleArrayStringValueToArrayRector::class,
    ]);

    $rectorConfig->sets([
        SetList::CARBON,
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::EARLY_RETURN,
        SetList::PHP_80,
        SetList::PHP_82,
        SetList::PHP_83,
        SetList::PHP_84,
        SetList::TYPE_DECLARATION,
        LaravelSetList::LARAVEL_100,
        LaravelSetList::LARAVEL_110,
        LaravelSetList::LARAVEL_CODE_QUALITY,
    ]);

    $rectorConfig->importNames();

    $rectorConfig->cacheClass(FileCacheStorage::class);

    $rectorConfig->cacheDirectory('./storage/rector/cache');
};
