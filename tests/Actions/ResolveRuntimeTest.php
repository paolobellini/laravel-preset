<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\ResolveRuntime;
use PaoloBellini\LaravelPreset\Enums\Runtime;
use PaoloBellini\LaravelPreset\Exceptions\InvalidRuntime;

afterEach(fn () => putenv('LARAVEL_SAIL'));

it('takes the runtime from the option', function () {
    configureSail($this->appBase);

    $resolved = app(ResolveRuntime::class)->handle($this->appBase, 'local', true);

    expect($resolved->runtime)->toBe(Runtime::Local)
        ->and($resolved->insideContainer)->toBeFalse();
});

it('rejects an unknown option', function () {
    app(ResolveRuntime::class)->handle($this->appBase, 'podman', true);
})->throws(InvalidRuntime::class, 'Unknown runtime [podman]. Use one of: sail, local.');

it('rejects the sail option when sail is not configured', function () {
    app(ResolveRuntime::class)->handle($this->appBase, 'sail', true);
})->throws(InvalidRuntime::class, 'The sail runtime needs Laravel Sail');

it('falls back to local without asking when sail is not configured', function () {
    expect(app(ResolveRuntime::class)->handle($this->appBase, null, true)->runtime)->toBe(Runtime::Local);
});

it('settles on sail without asking inside the container', function () {
    configureSail($this->appBase);
    putenv('LARAVEL_SAIL=1');

    $resolved = app(ResolveRuntime::class)->handle($this->appBase, null, true);

    expect($resolved->runtime)->toBe(Runtime::Sail)
        ->and($resolved->insideContainer)->toBeTrue();
});

it('takes the suggested runtime without asking when not interactive', function () {
    configureSail($this->appBase);

    expect(app(ResolveRuntime::class)->handle($this->appBase, null, false)->runtime)->toBe(Runtime::Sail);
});
