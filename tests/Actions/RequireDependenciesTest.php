<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use PaoloBellini\LaravelPreset\Actions\RequireDependencies;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Outcome;
use PaoloBellini\LaravelPreset\Enums\Runtime;

beforeEach(function () {
    $this->local = new ResolvedRuntime(Runtime::Local, insideContainer: false);
    $this->silent = fn (string $text) => null;
});

it('requires the runtime and the dev packages in two batches', function () {
    file_put_contents($this->appBase.'/composer.json', json_encode(['require' => ['php' => '^8.3']]));
    Process::fake();

    $outcomes = app(RequireDependencies::class)->handle($this->local, force: false, noInstall: false, write: $this->silent);

    expect($outcomes)->toBe(['composer require' => Outcome::Ran, 'composer require --dev' => Outcome::Ran]);

    Process::assertRan(fn ($process) => str_starts_with($process->command, 'composer require --no-interaction nunomaduro/essentials'));
    Process::assertRan(fn ($process) => str_starts_with($process->command, 'composer require --dev --no-interaction '));
});

it('skips a batch whose packages are all present', function () {
    file_put_contents($this->appBase.'/composer.json', json_encode(['require' => [
        'nunomaduro/essentials' => '*',
        'spatie/laravel-data' => '*',
        'spatie/laravel-query-builder' => '*',
        'spatie/laravel-typescript-transformer' => '*',
        'thecodingmachine/safe' => '*',
    ]]));
    Process::fake();

    $outcomes = app(RequireDependencies::class)->handle($this->local, force: false, noInstall: false, write: $this->silent);

    expect($outcomes['composer require'])->toBe(Outcome::Skipped)
        ->and($outcomes['composer require --dev'])->toBe(Outcome::Ran);
});

it('only writes the constraints with noInstall', function () {
    file_put_contents($this->appBase.'/composer.json', '{}');
    Process::fake();

    app(RequireDependencies::class)->handle($this->local, force: false, noInstall: true, write: $this->silent);

    Process::assertRan(fn ($process) => str_starts_with($process->command, 'composer require --no-interaction --no-update '));
});

it('prefixes composer with sail from the host', function () {
    file_put_contents($this->appBase.'/composer.json', '{}');
    Process::fake();

    app(RequireDependencies::class)->handle(
        new ResolvedRuntime(Runtime::Sail, insideContainer: false),
        force: false,
        noInstall: false,
        write: $this->silent,
    );

    Process::assertRan(fn ($process) => str_starts_with($process->command, './vendor/bin/sail composer require '));
});

it('reports a failing composer run', function () {
    file_put_contents($this->appBase.'/composer.json', '{}');
    Process::fake(['*' => Process::result(exitCode: 1)]);

    $outcomes = app(RequireDependencies::class)->handle($this->local, force: false, noInstall: false, write: $this->silent);

    expect($outcomes['composer require'])->toBe(Outcome::Failed);
});

it('does nothing without composer.json', function () {
    Process::fake();

    expect(app(RequireDependencies::class)->handle($this->local, force: false, noInstall: false, write: $this->silent))
        ->toBe([]);

    Process::assertNothingRan();
});
