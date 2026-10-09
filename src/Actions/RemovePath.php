<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

final readonly class RemovePath {
    public function __construct(
        private Application $app,
        private Filesystem $files,
    ) {}

    public function handle(string $path): bool {
        $target = $this->app->basePath($path);

        if ($this->files->isDirectory($target)) {
            return $this->files->deleteDirectory($target);
        }

        if ($this->files->exists($target)) {
            return $this->files->delete($target);
        }

        return false;
    }
}
