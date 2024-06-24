<?php

use Rector\Config\RectorConfig;
use Rector\EarlyReturn\Rector\If_\ChangeAndIfToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector;
use Rector\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
    ])
    ->withRules([
        DeclareStrictTypesRector::class,
        ChangeAndIfToEarlyReturnRector::class,
        ChangeIfElseValueAssignToEarlyReturnRector::class,
        ChangeNestedIfsToEarlyReturnRector::class,
        ReturnEarlyIfVariableRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true
    );
