<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\DetectPackages;
use PaoloBellini\LaravelPreset\Enums\Package;

it('finds the optional packages the project requires', function () {
    file_put_contents($this->appBase.'/composer.json', json_encode([
        'require' => ['spatie/laravel-data' => '^4.0'],
        'require-dev' => ['spatie/laravel-query-builder' => '^6.0'],
    ]));

    expect(app(DetectPackages::class)->handle())->toBe([Package::Data]);
});

it('finds nothing without composer.json', function () {
    expect(app(DetectPackages::class)->handle())->toBe([]);
});
