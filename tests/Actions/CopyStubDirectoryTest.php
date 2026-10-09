<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\CopyStubDirectory;
use PaoloBellini\LaravelPreset\Enums\Outcome;

it('copies every file of a stub directory, nested ones included', function () {
    $outcomes = app(CopyStubDirectory::class)->handle('github', '.github', force: false);

    expect($outcomes)->toHaveKey('.github/workflows/tests.yml', Outcome::Created)
        ->and($outcomes)->toHaveKey('.github/dependabot.yml', Outcome::Created)
        ->and($this->appBase.'/.github/workflows/tests.yml')->toBeFile();
});

it('leaves the files that already exist', function () {
    mkdir($this->appBase.'/.github');
    file_put_contents($this->appBase.'/.github/dependabot.yml', 'mine');

    $outcomes = app(CopyStubDirectory::class)->handle('github', '.github', force: false);

    expect($outcomes['.github/dependabot.yml'])->toBe(Outcome::Skipped)
        ->and(file_get_contents($this->appBase.'/.github/dependabot.yml'))->toBe('mine');
});
