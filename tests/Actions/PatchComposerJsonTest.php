<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\PatchComposerJson;
use PaoloBellini\LaravelPreset\Enums\Outcome;

it('merges the scripts and allows the pest plugin, keeping what was there', function () {
    file_put_contents($this->appBase.'/composer.json', json_encode([
        'name' => 'acme/app',
        'scripts' => ['dev' => 'npm run dev'],
        'config' => ['sort-packages' => true, 'allow-plugins' => ['php-http/discovery' => true]],
    ]));

    expect(app(PatchComposerJson::class)->handle())->toBe(Outcome::Patched);

    $composer = json_decode(file_get_contents($this->appBase.'/composer.json'), true);

    expect($composer['scripts'])->toHaveKeys(['dev', 'ci', 'pint', 'test:mutate'])
        ->and($composer['config']['sort-packages'])->toBeTrue()
        ->and($composer['config']['allow-plugins'])->toBe(['pestphp/pest-plugin' => true, 'php-http/discovery' => true]);
});

it('skips a project without composer.json', function () {
    expect(app(PatchComposerJson::class)->handle())->toBe(Outcome::Skipped)
        ->and($this->appBase.'/composer.json')->not->toBeFile();
});
