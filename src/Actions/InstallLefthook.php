<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use PaoloBellini\LaravelPreset\Enums\Outcome;
use PaoloBellini\LaravelPreset\Enums\Runtime;

final readonly class InstallLefthook {
    private const STUB = 'configs/lefthook.yml';

    private const FILE = 'lefthook.yml';

    public function __construct(
        private Application $app,
        private Filesystem $files,
        private CopyStub $copyStub,
    ) {}

    /**
     * @return array<string, Outcome>
     */
    public function handle(Runtime $runtime, bool $force): array {
        if (! $this->copyStub->handle(self::STUB, self::FILE, $force)) {
            return [self::FILE => Outcome::Skipped];
        }

        if ($runtime === Runtime::Local) {
            $target = $this->app->basePath(self::FILE);

            $this->files->put($target, str_replace(Runtime::SAIL_BINARY.' ', '', $this->files->get($target)));
        }

        return [self::FILE => Outcome::Created];
    }
}
