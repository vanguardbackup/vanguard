<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\EarlyReturn\Rector\If_\ChangeAndIfToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector;
use Rector\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->rules([
        DeclareStrictTypesRector::class,
        ChangeAndIfToEarlyReturnRector::class,
        ChangeIfElseValueAssignToEarlyReturnRector::class,
        ChangeNestedIfsToEarlyReturnRector::class,
        ReturnEarlyIfVariableRector::class,
    ]);

    $rectorConfig->sets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
    ]);

    $rectorConfig->importNames();

    $rectorConfig->cacheClass(FileCacheStorage::class);

    $rectorConfig->cacheDirectory('./storage/rector/cache');
};
