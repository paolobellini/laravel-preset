<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\CopyStub;

it('copies a stub into the project, creating the directories on the way', function () {
    $copied = app(CopyStub::class)->handle('configs/essentials.php', 'config/essentials.php', force: false);

    expect($copied)->toBeTrue()
        ->and($this->appBase.'/config/essentials.php')->toBeFile();
});

it('leaves an existing file alone', function () {
    file_put_contents($this->appBase.'/pint.json', '{}');

    $copied = app(CopyStub::class)->handle('configs/pint.json', 'pint.json', force: false);

    expect($copied)->toBeFalse()
        ->and(file_get_contents($this->appBase.'/pint.json'))->toBe('{}');
});

it('overwrites an existing file when forced', function () {
    file_put_contents($this->appBase.'/pint.json', '{}');

    $copied = app(CopyStub::class)->handle('configs/pint.json', 'pint.json', force: true);

    expect($copied)->toBeTrue()
        ->and(file_get_contents($this->appBase.'/pint.json'))->not->toBe('{}');
});
