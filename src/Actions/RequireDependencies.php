<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class RequireDependencies {
    public function __construct(
        private Application $app,
        private Filesystem $files,
        private BuildCommand $buildCommand,
        private RunProcess $runProcess,
    ) {}

    /**
     * @param  array<int, string>  $names
     * @param  Closure(string): void  $write
     * @return array<string, Outcome>
     */
    public function handle(
        ResolvedRuntime $resolved,
        array $names,
        bool $dev,
        bool $force,
        bool $noInstall,
        Closure $write,
    ): array {
        $path = $this->app->basePath('composer.json');

        if (! $this->files->exists($path)) {
            return [];
        }

        /** @var array<string, mixed> $composer */
        $composer = json_decode($this->files->get($path), true);

        /** @var array<string, string> $installed */
        $installed = $composer['require'] ?? [];
        $composerCommand = 'composer require';

        if ($dev) {
            /** @var array<string, string> $requireDev */
            $requireDev = $composer['require-dev'] ?? [];

            $installed += $requireDev;
            $composerCommand = 'composer require --dev';
        }

        $missing = [];

        foreach ($names as $name) {
            if ($force || ! array_key_exists($name, $installed)) {
                $missing[] = $name;
            }
        }

        if ($missing === []) {
            return [$composerCommand => Outcome::Skipped];
        }

        $flags = $noInstall ? '--no-interaction --no-update' : '--no-interaction';

        $command = $this->buildCommand->handle(
            $resolved,
            "{$composerCommand} {$flags} ".implode(' ', $missing),
        );

        $successful = $this->runProcess->handle($command, $write);

        return [$composerCommand => $successful ? Outcome::Ran : Outcome::Failed];
    }
}
