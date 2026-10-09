<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\InstallConfigs;
use PaoloBellini\LaravelPreset\Enums\Outcome;

it('creates every tooling config and reports each one', function () {
    $outcomes = app(InstallConfigs::class)->handle(force: false);

    expect($outcomes)->toBe([
        'pint.json' => Outcome::Created,
        'phpstan.neon' => Outcome::Created,
        'rector.php' => Outcome::Created,
        'rector-tests.php' => Outcome::Created,
        'psalm.xml' => Outcome::Created,
        'tests/Pest.php' => Outcome::Created,
    ]);

    foreach (array_keys($outcomes) as $file) {
        expect($this->appBase.'/'.$file)->toBeFile();
    }
});

it('reports the files it left alone', function () {
    file_put_contents($this->appBase.'/pint.json', '{}');

    $outcomes = app(InstallConfigs::class)->handle(force: false);

    expect($outcomes['pint.json'])->toBe(Outcome::Skipped)
        ->and($outcomes['phpstan.neon'])->toBe(Outcome::Created);
});
