<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\ReadPreset;
use PaoloBellini\LaravelPreset\Enums\Group;
use PaoloBellini\LaravelPreset\Enums\Package;
use PaoloBellini\LaravelPreset\Enums\Runtime;
use PaoloBellini\LaravelPreset\Exceptions\InvalidPreset;

it('finds nothing saved on a fresh project', function () {
    expect(app(ReadPreset::class)->handle())->toBeNull();
});

it('reads the saved choices', function () {
    file_put_contents($this->appBase.'/preset.json', json_encode([
        'runtime' => 'local',
        'packages' => ['data', 'query-builder'],
        'groups' => ['packages', 'github'],
    ]));

    $preset = app(ReadPreset::class)->handle();

    expect($preset->runtime)->toBe(Runtime::Local)
        ->and($preset->packages)->toBe([Package::Data, Package::QueryBuilder])
        ->and($preset->groups)->toBe([Group::Packages, Group::Github]);
});

it('rejects a file that is not the expected shape', function (string $contents) {
    file_put_contents($this->appBase.'/preset.json', $contents);

    app(ReadPreset::class)->handle();
})->with([
    'not json' => ['{'],
    'missing keys' => ['{"runtime":"local"}'],
    'wrong types' => ['{"runtime":"local","packages":"data","groups":[]}'],
])->throws(InvalidPreset::class, 'preset.json could not be read');

it('rejects an unknown value', function (array $saved, string $message) {
    file_put_contents($this->appBase.'/preset.json', json_encode($saved));

    expect(fn () => app(ReadPreset::class)->handle())->toThrow(InvalidPreset::class, $message);
})->with([
    'runtime' => [['runtime' => 'podman', 'packages' => [], 'groups' => []], 'unknown runtime [podman]'],
    'package' => [['runtime' => 'local', 'packages' => ['livewire'], 'groups' => []], 'unknown package [livewire]'],
    'group' => [['runtime' => 'local', 'packages' => [], 'groups' => ['docker']], 'unknown group [docker]'],
]);
