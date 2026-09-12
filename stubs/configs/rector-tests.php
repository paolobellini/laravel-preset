<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\CodingStyle\Rector\PostInc\PostIncDecToPreIncDecRector;
use Rector\Config\RectorConfig;
use RectorLaravel\Rector\StaticCall\CarbonToDateFacadeRector;
use RectorLaravel\Rector\StaticCall\DispatchToHelperFunctionsRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/tests',
    ])
    ->withComposerBased(laravel: true)
    ->withSets([
        LaravelSetList::LARAVEL_TESTING,
        PestSetList::CODING_STYLE,
    ])
    ->withSkip([
        CarbonToDateFacadeRector::class,
        DispatchToHelperFunctionsRector::class,
        PostIncDecToPreIncDecRector::class,
    ]);
