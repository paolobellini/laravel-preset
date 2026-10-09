<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class ConfigurePest {
    public const FILE = 'tests/Pest.php';

    private const STUB = 'configs/pest.php';

    private const TIA = <<<'PHP'

        pest()->tia()->locally();

        PHP;

    public function __construct(
        private Application $app,
        private Filesystem $files,
        private CopyStub $copyStub,
    ) {}

    public function handle(): Outcome {
        $target = $this->app->basePath(self::FILE);

        if (! $this->files->exists($target)) {
            $this->copyStub->handle(self::STUB, self::FILE, force: false);

            return Outcome::Created;
        }

        $contents = $this->files->get($target);

        if (str_contains($contents, '->tia(')) {
            return Outcome::Skipped;
        }

        $this->files->put($target, rtrim($contents, PHP_EOL).PHP_EOL.self::TIA);

        return Outcome::Patched;
    }
}
