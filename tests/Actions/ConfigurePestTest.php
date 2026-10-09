<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\ConfigurePest;
use PaoloBellini\LaravelPreset\Enums\Outcome;

it('creates tests/Pest.php when the project has none', function () {
    expect(app(ConfigurePest::class)->handle())->toBe(Outcome::Created)
        ->and(file_get_contents($this->appBase.'/tests/Pest.php'))->toContain('->tia(');
});

it('appends tia to an existing tests/Pest.php', function () {
    mkdir($this->appBase.'/tests');
    file_put_contents($this->appBase.'/tests/Pest.php', "<?php\n\npest()->extend(TestCase::class);\n");

    expect(app(ConfigurePest::class)->handle())->toBe(Outcome::Patched)
        ->and(file_get_contents($this->appBase.'/tests/Pest.php'))
        ->toContain('pest()->extend(TestCase::class);')
        ->toContain('pest()->tia()->locally();');
});

it('leaves a tests/Pest.php that already configures tia', function () {
    mkdir($this->appBase.'/tests');
    file_put_contents($this->appBase.'/tests/Pest.php', "<?php\n\npest()->tia()->always();\n");

    expect(app(ConfigurePest::class)->handle())->toBe(Outcome::Skipped)
        ->and(file_get_contents($this->appBase.'/tests/Pest.php'))->not->toContain('locally()');
});
