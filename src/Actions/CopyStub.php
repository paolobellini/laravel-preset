<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

final readonly class CopyStub {
    public function __construct(
        private Application $app,
        private Filesystem $files,
    ) {}

    public function handle(string $stub, string $destination, bool $force): bool {
        $target = $this->app->basePath($destination);

        if ($this->files->exists($target) && ! $force) {
            return false;
        }

        $this->files->ensureDirectoryExists(dirname($target));
        $this->files->copy(dirname(__DIR__, 2).'/stubs/'.$stub, $target);

        return true;
    }
}
