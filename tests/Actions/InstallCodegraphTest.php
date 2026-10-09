<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use Laravel\Prompts\Output\BufferedConsoleOutput;
use Laravel\Prompts\Prompt;
use PaoloBellini\LaravelPreset\Actions\InstallCodegraph;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Outcome;
use PaoloBellini\LaravelPreset\Enums\Runtime;

beforeEach(function () {
    $this->prompts = new BufferedConsoleOutput();
    Prompt::setOutput($this->prompts);

    $this->host = new ResolvedRuntime(Runtime::Local, insideContainer: false);
    $this->silent = fn (string $text) => null;
});

it('wires the project and builds the index', function () {
    Process::fake();

    $outcomes = app(InstallCodegraph::class)->handle($this->host, force: false, interactive: false, write: $this->silent);

    expect($outcomes)->toBe([
        'codegraph install --yes --target claude --location local' => Outcome::Ran,
        'codegraph init --yes' => Outcome::Ran,
    ]);
});

it('keeps an existing index unless forced', function () {
    Process::fake();
    mkdir($this->appBase.'/.codegraph');

    $kept = app(InstallCodegraph::class)->handle($this->host, force: false, interactive: false, write: $this->silent);
    $forced = app(InstallCodegraph::class)->handle($this->host, force: true, interactive: false, write: $this->silent);

    expect($kept)->toHaveKey('.codegraph', Outcome::Skipped)
        ->and($forced)->toHaveKey('codegraph init --yes', Outcome::Ran);
});

it('does not install a missing binary unasked', function () {
    Process::fake(['command -v codegraph' => Process::result(exitCode: 1)]);

    $outcomes = app(InstallCodegraph::class)->handle($this->host, force: false, interactive: false, write: $this->silent);

    expect($outcomes)->toBe(['codegraph' => Outcome::Skipped])
        ->and($this->prompts->content())->toContain('npm install --global @colbymchenry/codegraph');

    Process::assertDidntRun(fn ($process) => str_starts_with($process->command, 'npm '));
});

it('stays out of the sail container', function () {
    Process::fake();

    $outcomes = app(InstallCodegraph::class)->handle(
        new ResolvedRuntime(Runtime::Sail, insideContainer: true),
        force: false,
        interactive: true,
        write: $this->silent,
    );

    expect($outcomes)->toBe(['codegraph' => Outcome::Skipped])
        ->and($this->prompts->content())->toContain('outside Sail');

    Process::assertNothingRan();
});

it('stops when wiring the project fails', function () {
    Process::fake([
        'command -v codegraph' => Process::result(),
        '*' => Process::result(exitCode: 1),
    ]);

    $outcomes = app(InstallCodegraph::class)->handle($this->host, force: false, interactive: false, write: $this->silent);

    expect($outcomes)->toBe(['codegraph install --yes --target claude --location local' => Outcome::Failed]);
});
