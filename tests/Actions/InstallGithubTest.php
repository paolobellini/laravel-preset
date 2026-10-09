<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\InstallGithub;
use PaoloBellini\LaravelPreset\Enums\Outcome;

it('installs the workflows, dependabot and the vex document', function () {
    $outcomes = app(InstallGithub::class)->handle(force: false, sharded: false, renovate: false, renovateSelfHosted: false);

    expect($outcomes)->toHaveKey('.github/workflows/tests.yml', Outcome::Created)
        ->and($outcomes)->toHaveKey('.github/dependabot.yml', Outcome::Created)
        ->and($outcomes)->toHaveKey('.vex/openvex.json', Outcome::Created)
        ->and($outcomes)->not->toHaveKey('.github/renovate.json');
});

it('replaces the starter-kit workflows', function () {
    mkdir($this->appBase.'/.github/workflows', 0777, true);
    file_put_contents($this->appBase.'/.github/workflows/lint.yml', 'starter');
    file_put_contents($this->appBase.'/.github/workflows/tests.yml', 'starter');

    $outcomes = app(InstallGithub::class)->handle(force: false, sharded: false, renovate: false, renovateSelfHosted: false);

    expect($outcomes['.github/workflows/lint.yml'])->toBe(Outcome::Removed)
        ->and($outcomes['.github/workflows/tests.yml'])->toBe(Outcome::Created)
        ->and($this->appBase.'/.github/workflows/lint.yml')->not->toBeFile()
        ->and(file_get_contents($this->appBase.'/.github/workflows/tests.yml'))->not->toBe('starter');
});

it('swaps in the sharded tests workflow', function () {
    $outcomes = app(InstallGithub::class)->handle(force: false, sharded: true, renovate: false, renovateSelfHosted: false);

    expect($outcomes)->toHaveKey('.github/workflows/update-shards.yml', Outcome::Created)
        ->and(file_get_contents($this->appBase.'/.github/workflows/tests.yml'))->toContain('--shard=');
});

it('swaps dependabot for renovate', function () {
    $outcomes = app(InstallGithub::class)->handle(force: false, sharded: false, renovate: true, renovateSelfHosted: false);

    expect($outcomes['.github/renovate.json'])->toBe(Outcome::Created)
        ->and($outcomes['.github/dependabot.yml'])->toBe(Outcome::Removed)
        ->and($this->appBase.'/.github/dependabot.yml')->not->toBeFile()
        ->and($outcomes)->not->toHaveKey('.github/workflows/renovate.yml');
});

it('adds the self-hosted renovate workflow', function () {
    $outcomes = app(InstallGithub::class)->handle(force: false, sharded: false, renovate: false, renovateSelfHosted: true);

    expect($outcomes['.github/workflows/renovate.yml'])->toBe(Outcome::Created)
        ->and($outcomes['.github/renovate.json'])->toBe(Outcome::Created);
});
