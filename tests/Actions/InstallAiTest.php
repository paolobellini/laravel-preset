<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\InstallAi;
use PaoloBellini\LaravelPreset\Enums\Outcome;

it('copies the guidelines, removes the other agents and pins boost', function () {
    mkdir($this->appBase.'/.cursor');

    $outcomes = app(InstallAi::class)->handle(force: false);

    expect($outcomes)->toHaveKey('.ai/guidelines/personal/actions.md', Outcome::Created)
        ->and($outcomes)->toHaveKey('.cursor', Outcome::Removed)
        ->and($outcomes)->toHaveKey('boost.json', Outcome::Created)
        ->and($this->appBase.'/.ai/guidelines/personal/actions.md')->toBeFile();
});
