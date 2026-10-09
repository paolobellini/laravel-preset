<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use PaoloBellini\LaravelPreset\Enums\Package;

final readonly class DetectPackages {
    public function __construct(
        private Application $app,
        private Filesystem $files,
    ) {}

    /**
     * @return array<int, Package>
     */
    public function handle(): array {
        $path = $this->app->basePath('composer.json');

        if (! $this->files->exists($path)) {
            return [];
        }

        /** @var array<string, mixed> $composer */
        $composer = json_decode($this->files->get($path), true);

        /** @var array<string, string> $require */
        $require = $composer['require'] ?? [];

        $packages = [];

        foreach (Package::cases() as $package) {
            if (array_key_exists($package->composerName(), $require)) {
                $packages[] = $package;
            }
        }

        return $packages;
    }
}
