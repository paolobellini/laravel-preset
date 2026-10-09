<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\BuildCommand;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Runtime;

it('prefixes the command with sail from the host', function () {
    $resolved = new ResolvedRuntime(Runtime::Sail, insideContainer: false);

    expect((new BuildCommand())->handle($resolved, 'composer install'))
        ->toBe('./vendor/bin/sail composer install');
});

it('leaves the command bare for the local runtime', function () {
    $resolved = new ResolvedRuntime(Runtime::Local, insideContainer: false);

    expect((new BuildCommand())->handle($resolved, 'composer install'))->toBe('composer install');
});

it('leaves the command bare inside the container', function () {
    $resolved = new ResolvedRuntime(Runtime::Sail, insideContainer: true);

    expect((new BuildCommand())->handle($resolved, 'composer install'))->toBe('composer install');
});
