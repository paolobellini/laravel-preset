<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\ResolvePackages;
use PaoloBellini\LaravelPreset\Enums\Package;

it('leaves the typescript transformer out of a project without inertia', function () {
    file_put_contents($this->appBase.'/composer.json', json_encode(['require' => ['livewire/livewire' => '^3.0']]));

    expect(app(ResolvePackages::class)->handle(false))->toBe([Package::Data, Package::QueryBuilder]);
});

it('offers every package on an inertia project', function () {
    file_put_contents($this->appBase.'/composer.json', json_encode(['require' => ['inertiajs/inertia-laravel' => '^2.0']]));

    expect(app(ResolvePackages::class)->handle(false))->toBe(Package::cases());
});

it('leaves the typescript transformer out without composer.json', function () {
    expect(app(ResolvePackages::class)->handle(false))->toBe([Package::Data, Package::QueryBuilder]);
});
