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

it('requires the missing packages', function () {
    file_put_contents($this->appBase.'/composer.json', json_encode(['require' => ['acme/one' => '*']]));
    Process::fake();

    $outcomes = app(RequireDependencies::class)->handle(
        $this->local, ['acme/one', 'acme/two'], dev: false, force: false, noInstall: false, write: $this->silent,
    );

    expect($outcomes)->toBe(['composer require' => Outcome::Ran]);

    Process::assertRan(fn ($process) => $process->command === 'composer require --no-interaction acme/two');
});

it('requires dev packages with --dev', function () {
    file_put_contents($this->appBase.'/composer.json', '{}');
    Process::fake();

    $outcomes = app(RequireDependencies::class)->handle(
        $this->local, ['acme/one'], dev: true, force: false, noInstall: false, write: $this->silent,
    );

    expect($outcomes)->toBe(['composer require --dev' => Outcome::Ran]);

    Process::assertRan(fn ($process) => $process->command === 'composer require --dev --no-interaction acme/one');
});

it('counts a dev package as present wherever it is required', function () {
    file_put_contents($this->appBase.'/composer.json', json_encode([
        'require' => ['acme/one' => '*'],
        'require-dev' => ['acme/two' => '*'],
    ]));
    Process::fake();

    $outcomes = app(RequireDependencies::class)->handle(
        $this->local, ['acme/one', 'acme/two'], dev: true, force: false, noInstall: false, write: $this->silent,
    );

    expect($outcomes)->toBe(['composer require --dev' => Outcome::Skipped]);

    Process::assertNothingRan();
});

it('requires a runtime package again when it sits in require-dev', function () {
    file_put_contents($this->appBase.'/composer.json', json_encode(['require-dev' => ['acme/one' => '*']]));
    Process::fake();

    app(RequireDependencies::class)->handle(
        $this->local, ['acme/one'], dev: false, force: false, noInstall: false, write: $this->silent,
    );

    Process::assertRan(fn ($process) => $process->command === 'composer require --no-interaction acme/one');
});

it('requires everything again when forced', function () {
    file_put_contents($this->appBase.'/composer.json', json_encode(['require' => ['acme/one' => '*']]));
    Process::fake();

    app(RequireDependencies::class)->handle(
        $this->local, ['acme/one'], dev: false, force: true, noInstall: false, write: $this->silent,
    );

    Process::assertRan(fn ($process) => $process->command === 'composer require --no-interaction acme/one');
});

it('only writes the constraints with noInstall', function () {
    file_put_contents($this->appBase.'/composer.json', '{}');
    Process::fake();

    app(RequireDependencies::class)->handle(
        $this->local, ['acme/one'], dev: false, force: false, noInstall: true, write: $this->silent,
    );

    Process::assertRan(fn ($process) => $process->command === 'composer require --no-interaction --no-update acme/one');
});

it('prefixes composer with sail from the host', function () {
    file_put_contents($this->appBase.'/composer.json', '{}');
    Process::fake();

    app(RequireDependencies::class)->handle(
        new ResolvedRuntime(Runtime::Sail, insideContainer: false),
        ['acme/one'],
        dev: false,
        force: false,
        noInstall: false,
        write: $this->silent,
    );

    Process::assertRan(fn ($process) => str_starts_with($process->command, './vendor/bin/sail composer require '));
});

it('reports a failing composer run', function () {
    file_put_contents($this->appBase.'/composer.json', '{}');
    Process::fake(['*' => Process::result(exitCode: 1)]);

    $outcomes = app(RequireDependencies::class)->handle(
        $this->local, ['acme/one'], dev: false, force: false, noInstall: false, write: $this->silent,
    );

    expect($outcomes)->toBe(['composer require' => Outcome::Failed]);
});

it('does nothing without composer.json', function () {
    Process::fake();

    $outcomes = app(RequireDependencies::class)->handle(
        $this->local, ['acme/one'], dev: false, force: false, noInstall: false, write: $this->silent,
    );

    expect($outcomes)->toBe([]);

    Process::assertNothingRan();
});
