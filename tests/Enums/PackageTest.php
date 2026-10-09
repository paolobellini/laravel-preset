<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Enums\Package;

it('labels every package', function (Package $package, string $label) {
    expect($package->label())->toBe($label);
})->with([
    [Package::Data, 'Laravel Data — typed data objects'],
    [Package::QueryBuilder, 'Laravel Query Builder — filters, sorts and includes from the request'],
    [Package::TypescriptTransformer, 'TypeScript Transformer — TypeScript types from PHP classes'],
]);

it('names the composer package', function (Package $package, string $name) {
    expect($package->composerName())->toBe($name);
})->with([
    [Package::Data, 'spatie/laravel-data'],
    [Package::QueryBuilder, 'spatie/laravel-query-builder'],
    [Package::TypescriptTransformer, 'spatie/laravel-typescript-transformer'],
]);

it('ties only the typescript transformer to inertia', function () {
    expect(Package::Data->needsInertia())->toBeFalse()
        ->and(Package::QueryBuilder->needsInertia())->toBeFalse()
        ->and(Package::TypescriptTransformer->needsInertia())->toBeTrue();
});

it('points to the guideline that only makes sense with the package', function () {
    expect(Package::Data->guideline())->toBeNull()
        ->and(Package::QueryBuilder->guideline())->toBe('guidelines/personal/query-builder.md')
        ->and(Package::TypescriptTransformer->guideline())->toBe('guidelines/personal/typescript.md');
});
