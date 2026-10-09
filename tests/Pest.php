<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function configureSail(string $base, ?string $composeFile = 'compose.yaml'): void
{
    mkdir($base.'/vendor/bin', 0777, true);
    file_put_contents($base.'/vendor/bin/sail', "#!/bin/sh\n");

    if ($composeFile !== null) {
        file_put_contents($base.'/'.$composeFile, "services: {}\n");
    }
}
