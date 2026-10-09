<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class PinBoostAgent {
    public const FILE = 'boost.json';

    private const AGENT = 'claude_code';

    public function __construct(
        private Application $app,
        private Filesystem $files,
    ) {}

    public function handle(): Outcome {
        $path = $this->app->basePath(self::FILE);
        $exists = $this->files->exists($path);

        /** @var array<string, mixed> $config */
        $config = $exists ? (json_decode($this->files->get($path), true) ?? []) : [];

        if (($config['agents'] ?? null) === [self::AGENT]) {
            return Outcome::Skipped;
        }

        $config['agents'] = [self::AGENT];
        ksort($config);

        $this->files->put($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        return $exists ? Outcome::Patched : Outcome::Created;
    }
}
