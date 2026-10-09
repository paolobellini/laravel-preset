<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use PaoloBellini\LaravelPreset\Enums\Package;

use function Laravel\Prompts\multiselect;

final readonly class ResolvePackages {
    private const INERTIA = 'inertiajs/inertia-laravel';

    public function __construct(
        private Application $app,
        private Filesystem $files,
    ) {}

    /**
     * @return array<int, Package>
     */
    public function handle(bool $interactive): array {
        $offered = $this->offered();

        if (! $interactive || $offered === []) {
            return $offered;
        }

        $options = [];

        foreach ($offered as $package) {
            $options[$package->value] = $package->label();
        }

        /** @var array<int, string> $selected */
        $selected = multiselect(
            label: 'Which optional packages does this project need?',
            options: $options,
            default: array_keys($options),
            hint: 'nunomaduro/essentials and thecodingmachine/safe are always installed.',
        );

        return array_map(Package::from(...), $selected);
    }

    /**
     * @return array<int, Package>
     */
    private function offered(): array {
        $usesInertia = $this->usesInertia();
        $offered = [];

        foreach (Package::cases() as $package) {
            if ($package->needsInertia() && ! $usesInertia) {
                continue;
            }

            $offered[] = $package;
        }

        return $offered;
    }

    private function usesInertia(): bool {
        $path = $this->app->basePath('composer.json');

        if (! $this->files->exists($path)) {
            return false;
        }

        /** @var array<string, mixed> $composer */
        $composer = json_decode($this->files->get($path), true);

        /** @var array<string, string> $require */
        $require = $composer['require'] ?? [];

        return array_key_exists(self::INERTIA, $require);
    }
}
