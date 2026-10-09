<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Enums\Outcome;

it('labels every outcome', function (Outcome $outcome, string $label) {
    expect($outcome->label())->toBe($label);
})->with([
    [Outcome::Created, 'created'],
    [Outcome::Patched, 'patched'],
    [Outcome::Removed, 'removed'],
    [Outcome::Skipped, 'skipped'],
    [Outcome::Ran, 'done'],
    [Outcome::Failed, 'failed'],
]);
