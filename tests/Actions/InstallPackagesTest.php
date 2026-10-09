<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use PaoloBellini\LaravelPreset\Actions\InstallPackages;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Outcome;
use PaoloBellini\LaravelPreset\Enums\Package;
use PaoloBellini\LaravelPreset\Enums\Runtime;

beforeEach(function () {
    file_put_contents($this->appBase.'/composer.json', '{}');
    Process::fake();

    $this->local = new ResolvedRuntime(Runtime::Local, insideContainer: false);
});

it('requires the mandatory packages alone', function () {
    $outcomes = app(InstallPackages::class)->handle(
        $this->local, [], force: false, noInstall: false, write: fn (string $text) => null,
    );

    expect($outcomes)->toBe(['config/essentials.php' => Outcome::Created, 'composer require' => Outcome::Ran])
        ->and($this->appBase.'/config/essentials.php')->toBeFile();

    Process::assertRan(fn ($process) => $process->command
        === 'composer require --no-interaction nunomaduro/essentials thecodingmachine/safe');
});

it('adds the chosen packages to the mandatory ones', function () {
    app(InstallPackages::class)->handle(
        $this->local,
        [Package::QueryBuilder, Package::Data],
        force: false,
        noInstall: false,
        write: fn (string $text) => null,
    );

    Process::assertRan(fn ($process) => $process->command
        === 'composer require --no-interaction nunomaduro/essentials spatie/laravel-data spatie/laravel-query-builder thecodingmachine/safe');
});
