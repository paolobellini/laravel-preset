<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Illuminate\Filesystem\Filesystem;
use PaoloBellini\LaravelPreset\Data\Environment;
use PaoloBellini\LaravelPreset\Enums\Runtime;

final readonly class DetectEnvironment {
    private const SAIL_ENV_VARIABLE = 'LARAVEL_SAIL';

    public function __construct(private Filesystem $files) {}

    public function handle(string $basePath): Environment {
        return new Environment(
            sailConfigured: $this->hasSailBinary($basePath) && $this->hasComposeFile($basePath),
            insideContainer: (bool) getenv(self::SAIL_ENV_VARIABLE),
        );
    }

    private function hasSailBinary(string $basePath): bool {
        return $this->files->exists($basePath.'/'.Runtime::SAIL_BINARY);
    }

    private function hasComposeFile(string $basePath): bool {
        return $this->files->exists($basePath.'/compose.yaml')
            || $this->files->exists($basePath.'/docker-compose.yml');
    }
}
