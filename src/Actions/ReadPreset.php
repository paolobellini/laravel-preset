<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use PaoloBellini\LaravelPreset\Data\Preset;
use PaoloBellini\LaravelPreset\Enums\Group;
use PaoloBellini\LaravelPreset\Enums\Package;
use PaoloBellini\LaravelPreset\Enums\Runtime;
use PaoloBellini\LaravelPreset\Exceptions\InvalidPreset;

final readonly class ReadPreset {
    public function __construct(
        private Application $app,
        private Filesystem $files,
    ) {}

    public function handle(): ?Preset {
        $path = $this->app->basePath(WritePreset::FILE);

        if (! $this->files->exists($path)) {
            return null;
        }

        $saved = json_decode($this->files->get($path), true);

        if (! is_array($saved)) {
            throw InvalidPreset::malformed();
        }

        $runtimeName = $saved['runtime'] ?? null;
        $packageNames = $saved['packages'] ?? null;
        $groupNames = $saved['groups'] ?? null;

        if (! is_string($runtimeName) || ! is_array($packageNames) || ! is_array($groupNames)) {
            throw InvalidPreset::malformed();
        }

        $runtime = Runtime::tryFrom($runtimeName);

        if ($runtime === null) {
            throw InvalidPreset::unknown('runtime', $runtimeName);
        }

        $packages = [];

        foreach ($packageNames as $name) {
            $package = is_string($name) ? Package::tryFrom($name) : null;

            if ($package === null) {
                throw InvalidPreset::unknown('package', is_string($name) ? $name : gettype($name));
            }

            $packages[] = $package;
        }

        $groups = [];

        foreach ($groupNames as $name) {
            $group = is_string($name) ? Group::tryFrom($name) : null;

            if ($group === null) {
                throw InvalidPreset::unknown('group', is_string($name) ? $name : gettype($name));
            }

            $groups[] = $group;
        }

        return new Preset($runtime, $packages, $groups);
    }
}
