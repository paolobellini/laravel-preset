<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\RemovePath;

it('removes a file', function () {
    file_put_contents($this->appBase.'/stale.txt', '');

    expect(app(RemovePath::class)->handle('stale.txt'))->toBeTrue()
        ->and($this->appBase.'/stale.txt')->not->toBeFile();
});

it('removes a directory with its contents', function () {
    mkdir($this->appBase.'/.cursor/rules', 0777, true);
    file_put_contents($this->appBase.'/.cursor/rules/a.md', '');

    expect(app(RemovePath::class)->handle('.cursor'))->toBeTrue()
        ->and($this->appBase.'/.cursor')->not->toBeDirectory();
});

it('reports nothing removed when the path is missing', function () {
    expect(app(RemovePath::class)->handle('missing'))->toBeFalse();
});
