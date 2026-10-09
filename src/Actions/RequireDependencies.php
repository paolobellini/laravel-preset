<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Outcome;
use PaoloBellini\LaravelPreset\Enums\Package;

final readonly class RequireDependencies {
    /**
     * @var array<int, string>
     */
    private const REQUIRE = [
        'nunomaduro/essentials',
        'thecodingmachine/safe',
    ];

    /**
     * @var array<int, string>
     */
    private const REQUIRE_DEV = [
        'driftingly/rector-laravel',
        'fruitcake/laravel-debugbar',
        'larastan/larastan',
        'laravel/boost',
        'laravel/pail',
        'laravel/pint',
        'pestphp/pest',
        'pestphp/pest-plugin-agent',
        'pestphp/pest-plugin-evals',
        'pestphp/pest-plugin-faker',
        'pestphp/pest-plugin-mutate',
        'pestphp/pest-plugin-phpstan',
        'pestphp/pest-plugin-rector',
        'pestphp/pest-plugin-type-coverage',
        'rector/rector',
        'thecodingmachine/phpstan-safe-rule',
        'vimeo/psalm',
    ];

    public function __construct(
        private Application $app,
        private Filesystem $files,
        private BuildCommand $buildCommand,
        private RunProcess $runProcess,
    ) {}

    /**
     * @param  array<int, Package>  $packages
     * @param  Closure(string): void  $write
     * @return array<string, Outcome>
     */
    public function handle(ResolvedRuntime $resolved, array $packages, bool $force, bool $noInstall, Closure $write): array {
        $path = $this->app->basePath('composer.json');

        if (! $this->files->exists($path)) {
            return [];
        }

        /** @var array<string, mixed> $composer */
        $composer = json_decode($this->files->get($path), true);

        /** @var array<string, string> $require */
        $require = $composer['require'] ?? [];
        /** @var array<string, string> $requireDev */
        $requireDev = $composer['require-dev'] ?? [];

        $runtime = self::REQUIRE;

        foreach ($packages as $package) {
            $runtime[] = $package->composerName();
        }

        sort($runtime);

        $flags = $noInstall ? '--no-interaction --no-update' : '--no-interaction';

        $batches = [
            'composer require' => $this->missing($runtime, $require, $force),
            'composer require --dev' => $this->missing(self::REQUIRE_DEV, $require + $requireDev, $force),
        ];

        $outcomes = [];

        foreach ($batches as $composerCommand => $names) {
            if ($names === []) {
                $outcomes[$composerCommand] = Outcome::Skipped;

                continue;
            }

            $command = $this->buildCommand->handle(
                $resolved,
                "{$composerCommand} {$flags} ".implode(' ', $names),
            );

            $outcomes[$composerCommand] = $this->runProcess->handle($command, $write)
                ? Outcome::Ran
                : Outcome::Failed;
        }

        return $outcomes;
    }

    /**
     * @param  array<int, string>  $packages
     * @param  array<string, string>  $installed
     * @return array<int, string>
     */
    private function missing(array $packages, array $installed, bool $force): array {
        if ($force) {
            return $packages;
        }

        return array_values(array_filter(
            $packages,
            fn (string $package): bool => ! array_key_exists($package, $installed),
        ));
    }
}
