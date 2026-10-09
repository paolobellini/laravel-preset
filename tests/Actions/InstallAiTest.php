<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use PaoloBellini\LaravelPreset\Actions\InstallAi;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Outcome;
use PaoloBellini\LaravelPreset\Enums\Package;
use PaoloBellini\LaravelPreset\Enums\Runtime;

beforeEach(function () {
    file_put_contents($this->appBase.'/composer.json', '{}');
    Process::fake();

    $this->local = new ResolvedRuntime(Runtime::Local, insideContainer: false);
    $this->silent = fn (string $text) => null;
});

it('copies the guidelines, removes the other agents, pins and requires boost', function () {
    mkdir($this->appBase.'/.cursor');

    $outcomes = app(InstallAi::class)->handle($this->local, [], force: false, noInstall: false, write: $this->silent);

    expect($outcomes)->toHaveKey('.ai/guidelines/personal/actions.md', Outcome::Created)
        ->and($outcomes)->toHaveKey('.cursor', Outcome::Removed)
        ->and($outcomes)->toHaveKey('boost.json', Outcome::Created)
        ->and($outcomes)->toHaveKey('composer require --dev', Outcome::Ran)
        ->and($this->appBase.'/.ai/guidelines/personal/actions.md')->toBeFile();

    Process::assertRan(fn ($process) => $process->command === 'composer require --dev --no-interaction laravel/boost');
});

it('leaves out the guidelines of the packages the project does not have', function () {
    $outcomes = app(InstallAi::class)->handle($this->local, [Package::Data], force: false, noInstall: false, write: $this->silent);

    expect($outcomes)->not->toHaveKey('.ai/guidelines/personal/query-builder.md')
        ->and($outcomes)->not->toHaveKey('.ai/guidelines/personal/typescript.md')
        ->and($this->appBase.'/.ai/guidelines/personal/query-builder.md')->not->toBeFile();
});

it('copies the guidelines of the packages the project has', function () {
    $outcomes = app(InstallAi::class)->handle(
        $this->local,
        [Package::QueryBuilder, Package::TypescriptTransformer],
        force: false,
        noInstall: false,
        write: $this->silent,
    );

    expect($outcomes)->toHaveKey('.ai/guidelines/personal/query-builder.md', Outcome::Created)
        ->and($outcomes)->toHaveKey('.ai/guidelines/personal/typescript.md', Outcome::Created);
});
