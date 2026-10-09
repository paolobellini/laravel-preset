<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\InstallLefthook;
use PaoloBellini\LaravelPreset\Enums\Outcome;
use PaoloBellini\LaravelPreset\Enums\Runtime;

it('keeps the sail prefix for the sail runtime', function () {
    $outcomes = app(InstallLefthook::class)->handle(Runtime::Sail, force: false);

    expect($outcomes)->toBe(['lefthook.yml' => Outcome::Created])
        ->and(file_get_contents($this->appBase.'/lefthook.yml'))->toContain('vendor/bin/sail composer pint');
});

it('strips the sail prefix for the local runtime', function () {
    app(InstallLefthook::class)->handle(Runtime::Local, force: false);

    expect(file_get_contents($this->appBase.'/lefthook.yml'))
        ->not->toContain('vendor/bin/sail')
        ->toContain('run: composer pint')
        ->toContain('run: npx prettier');
});

it('leaves an existing lefthook.yml untouched', function () {
    file_put_contents($this->appBase.'/lefthook.yml', 'mine');

    expect(app(InstallLefthook::class)->handle(Runtime::Local, force: false))
        ->toBe(['lefthook.yml' => Outcome::Skipped])
        ->and(file_get_contents($this->appBase.'/lefthook.yml'))->toBe('mine');
});
