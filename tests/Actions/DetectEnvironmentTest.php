<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\DetectEnvironment;

it('finds sail configured with either compose file name', function (string $composeFile) {
    configureSail($this->appBase, $composeFile);

    expect(app(DetectEnvironment::class)->handle($this->appBase)->sailConfigured)->toBeTrue();
})->with(['compose.yaml', 'docker-compose.yml']);

it('does not count sail as configured without a compose file', function () {
    configureSail($this->appBase, null);

    expect(app(DetectEnvironment::class)->handle($this->appBase)->sailConfigured)->toBeFalse();
});

it('does not count sail as configured without its binary', function () {
    file_put_contents($this->appBase.'/compose.yaml', "services: {}\n");

    expect(app(DetectEnvironment::class)->handle($this->appBase)->sailConfigured)->toBeFalse();
});

it('knows when it runs inside the sail container', function () {
    putenv('LARAVEL_SAIL=1');

    try {
        $inside = app(DetectEnvironment::class)->handle($this->appBase)->insideContainer;
    } finally {
        putenv('LARAVEL_SAIL');
    }

    expect($inside)->toBeTrue()
        ->and(app(DetectEnvironment::class)->handle($this->appBase)->insideContainer)->toBeFalse();
});
