<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class CreateVexDocument {
    public const FILE = '.vex/openvex.json';

    private const STUB = 'vex/openvex.json';

    public function __construct(
        private Application $app,
        private Filesystem $files,
        private CopyStub $copyStub,
    ) {}

    public function handle(bool $force): Outcome {
        if (! $this->copyStub->handle(self::STUB, self::FILE, $force)) {
            return Outcome::Skipped;
        }

        $path = $this->app->basePath(self::FILE);

        /** @var array<string, mixed> $document */
        $document = json_decode($this->files->get($path), true);

        $document['@id'] = 'urn:vex:'.str_replace('/', ':', $this->projectName());
        $document['timestamp'] = gmdate('Y-m-d\\TH:i:s\\Z');

        $this->files->put($path, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        return Outcome::Created;
    }

    private function projectName(): string {
        $path = $this->app->basePath('composer.json');

        if ($this->files->exists($path)) {
            /** @var array<string, mixed> $composer */
            $composer = json_decode($this->files->get($path), true);

            if (is_string($composer['name'] ?? null) && $composer['name'] !== '') {
                return $composer['name'];
            }
        }

        return basename($this->app->basePath());
    }
}
