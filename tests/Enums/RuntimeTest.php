<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Data\Environment;
use PaoloBellini\LaravelPreset\Enums\Runtime;

it('labels every runtime', function () {
    expect(Runtime::Sail->label())->toBe('Laravel Sail')
        ->and(Runtime::Local->label())->toBe('Local');
});

it('suggests sail when it is configured', function () {
    $environment = new Environment(sailConfigured: true, insideContainer: false);

    expect(Runtime::suggestedFor($environment))->toBe(Runtime::Sail);
});

it('suggests local when sail is not configured', function () {
    $environment = new Environment(sailConfigured: false, insideContainer: false);

    expect(Runtime::suggestedFor($environment))->toBe(Runtime::Local);
});
