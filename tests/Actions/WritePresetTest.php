<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\WritePreset;
use PaoloBellini\LaravelPreset\Data\Preset;
use PaoloBellini\LaravelPreset\Enums\Group;
use PaoloBellini\LaravelPreset\Enums\Outcome;
use PaoloBellini\LaravelPreset\Enums\Package;
use PaoloBellini\LaravelPreset\Enums\Runtime;

it('saves the choices as preset.json', function () {
    $preset = new Preset(Runtime::Sail, [Package::Data], [Group::Packages, Group::Ai]);

    expect(app(WritePreset::class)->handle($preset))->toBe(Outcome::Created)
        ->and(json_decode(file_get_contents($this->appBase.'/preset.json'), true))->toBe([
            'runtime' => 'sail',
            'packages' => ['data'],
            'groups' => ['packages', 'ai'],
        ]);
});

it('replaces the choices saved before', function () {
    file_put_contents($this->appBase.'/preset.json', '{"runtime":"sail","packages":[],"groups":[]}');

    $preset = new Preset(Runtime::Local, [], [Group::Lefthook]);

    expect(app(WritePreset::class)->handle($preset))->toBe(Outcome::Patched)
        ->and(json_decode(file_get_contents($this->appBase.'/preset.json'), true)['runtime'])->toBe('local');
});
