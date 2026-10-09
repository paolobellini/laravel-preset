<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use PaoloBellini\LaravelPreset\Data\Preset;
use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class WritePreset {
    public const FILE = 'preset.json';

    public function __construct(
        private Application $app,
        private Filesystem $files,
    ) {}

    public function handle(Preset $preset): Outcome {
        $path = $this->app->basePath(self::FILE);
        $existed = $this->files->exists($path);

        $contents = json_encode([
            'runtime' => $preset->runtime->value,
            'packages' => array_column($preset->packages, 'value'),
            'groups' => array_column($preset->groups, 'value'),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        $this->files->put($path, $contents.PHP_EOL);

        return $existed ? Outcome::Patched : Outcome::Created;
    }
}
