<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use PaoloBellini\LaravelPreset\Actions\InstallSkills;
use PaoloBellini\LaravelPreset\Enums\Outcome;

it('adds the skills of every source for the universal and claude code agents', function () {
    Process::fake();

    $outcomes = app(InstallSkills::class)->handle(fn (string $text) => null);

    expect($outcomes)->toBe([
        'jpcaparas/superpowers-laravel' => Outcome::Ran,
        'mattpocock/skills' => Outcome::Ran,
    ]);

    Process::assertRan(fn ($process) => $process->command
        === 'npx --yes skills@latest add mattpocock/skills --skill wait-what --skill teach --agent universal --agent claude-code');
});

it('reports a source that failed and carries on with the next', function () {
    Process::fake([
        '*superpowers-laravel*' => Process::result(exitCode: 1),
        '*' => Process::result(),
    ]);

    expect(app(InstallSkills::class)->handle(fn (string $text) => null))->toBe([
        'jpcaparas/superpowers-laravel' => Outcome::Failed,
        'mattpocock/skills' => Outcome::Ran,
    ]);
});
