<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Illuminate\Filesystem\Filesystem;
use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class CopyStubDirectory {
    public function __construct(
        private Filesystem $files,
        private CopyStub $copyStub,
    ) {}

    /**
     * @param  array<int, string>  $except
     * @return array<string, Outcome>
     */
    public function handle(string $stub, string $destination, bool $force, array $except = []): array {
        $outcomes = [];

        foreach ($this->files->allFiles(dirname(__DIR__, 2).'/stubs/'.$stub) as $file) {
            $relative = $file->getRelativePathname();

            if (in_array($relative, $except, true)) {
                continue;
            }

            $target = $destination.'/'.$relative;

            $outcomes[$target] = $this->copyStub->handle($stub.'/'.$relative, $target, $force)
                ? Outcome::Created
                : Outcome::Skipped;
        }

        return $outcomes;
    }
}
