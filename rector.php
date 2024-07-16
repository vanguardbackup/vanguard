<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodingStyle\Rector\Encapsed\WrapEncapsedVariableInCurlyBracesRector;
use Rector\CodingStyle\Rector\String_\SymplifyQuoteEscapeRector;
use Rector\CodingStyle\Rector\Use_\SeparateMultiUseImportsRector;
use Rector\Config\RectorConfig;
use Rector\CustomRules\ReplaceModelAttributesRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
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

        // Rector Rules
        DeclareStrictTypesRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        SeparateMultiUseImportsRector::class,
        SymplifyQuoteEscapeRector::class,
        WrapEncapsedVariableInCurlyBracesRector::class,
    ]);

    $rectorConfig->sets([
        SetList::NAMING,
        SetList::CARBON,
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::EARLY_RETURN,
        SetList::PHP_80,
        SetList::PHP_81,
        SetList::PHP_82,
        SetList::PHP_83,
        SetList::PHP_84,
        SetList::TYPE_DECLARATION,
        SetList::INSTANCEOF,
        LaravelSetList::ARRAY_STR_FUNCTIONS_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_100,
        LaravelSetList::LARAVEL_110,
        LaravelSetList::LARAVEL_CODE_QUALITY,
    ]);

    $rectorConfig->skip([
        ReadOnlyPropertyRector::class,
        // TODO - Fix the readonly changes this makes that are problematic.
    ]);

    $rectorConfig->importNames();

    $rectorConfig->cacheClass(FileCacheStorage::class);

    $rectorConfig->cacheDirectory('./storage/rector/cache');
};
