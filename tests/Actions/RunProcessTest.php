<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use PaoloBellini\LaravelPreset\Actions\RunProcess;

it('streams the command and what it prints', function () {
    $written = '';

    $successful = app(RunProcess::class)->handle('printf streamed', function (string $text) use (&$written): void {
        $written .= $text;
    });

    expect($successful)->toBeTrue()
        ->and($written)->toContain('$ printf streamed')
        ->and($written)->toEndWith('streamed');
});

it('runs the command in the project', function () {
    Process::fake();

    app(RunProcess::class)->handle('composer install', fn (string $text) => null);

    Process::assertRan(fn ($process) => $process->command === 'composer install' && $process->path === $this->appBase);
});

it('reports a failing command', function () {
    Process::fake(['composer install' => Process::result(exitCode: 1)]);

    expect(app(RunProcess::class)->handle('composer install', fn (string $text) => null))->toBeFalse();
});
