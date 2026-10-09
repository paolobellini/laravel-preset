<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use PaoloBellini\LaravelPreset\Actions\InstallScripts;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Outcome;
use PaoloBellini\LaravelPreset\Enums\Runtime;

it('patches composer.json and requires the dev dependencies', function () {
    file_put_contents($this->appBase.'/composer.json', '{}');
    Process::fake();

    $outcomes = app(InstallScripts::class)->handle(
        new ResolvedRuntime(Runtime::Local, insideContainer: false),
        force: false,
        noInstall: false,
        write: fn (string $text) => null,
    );

    expect($outcomes)->toBe([
        'composer.json' => Outcome::Patched,
        'composer require --dev' => Outcome::Ran,
    ]);

    Process::assertRan(fn ($process) => str_contains($process->command, 'pestphp/pest ')
        && str_contains($process->command, 'vimeo/psalm'));
    Process::assertNotRan(fn ($process) => str_contains($process->command, 'nunomaduro/essentials'));
});
