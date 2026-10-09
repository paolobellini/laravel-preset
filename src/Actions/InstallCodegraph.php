<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Outcome;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\warning;

final readonly class InstallCodegraph {
    private const DIRECTORY = '.codegraph';

    private const INSTALLER = 'npm install --global @colbymchenry/codegraph';

    private const WIRE = 'codegraph install --yes --target claude --location local';

    private const INIT = 'codegraph init --yes';

    public function __construct(
        private Application $app,
        private Filesystem $files,
        private RunProcess $runProcess,
    ) {}

    /**
     * @param  Closure(string): void  $write
     * @return array<string, Outcome>
     */
    public function handle(ResolvedRuntime $resolved, bool $force, bool $interactive, Closure $write): array {
        if ($resolved->insideContainer) {
            warning(
                'CodeGraph runs on the host, not in the Sail container. '
                .'Run `php artisan preset:install --codegraph` outside Sail.'
            );

            return ['codegraph' => Outcome::Skipped];
        }

        if (! $this->isInstalled() && ! $this->installBinary($interactive, $write)) {
            return ['codegraph' => Outcome::Skipped];
        }

        if (! $this->runProcess->handle(self::WIRE, $write)) {
            return [self::WIRE => Outcome::Failed];
        }

        if ($this->files->isDirectory($this->app->basePath(self::DIRECTORY)) && ! $force) {
            return [self::WIRE => Outcome::Ran, self::DIRECTORY => Outcome::Skipped];
        }

        return [
            self::WIRE => Outcome::Ran,
            self::INIT => $this->runProcess->handle(self::INIT, $write) ? Outcome::Ran : Outcome::Failed,
        ];
    }

    private function isInstalled(): bool {
        return Process::path($this->app->basePath())->run('command -v codegraph')->successful();
    }

    /**
     * @param  Closure(string): void  $write
     */
    private function installBinary(bool $interactive, Closure $write): bool {
        if (! $interactive) {
            warning('CodeGraph is not on your PATH. Install it with `'.self::INSTALLER.'`, then run this again.');

            return false;
        }

        if (! confirm(label: 'CodeGraph is not installed. Install it now?', default: false)) {
            warning('Skipped. Run `'.self::INSTALLER.'` when you want it.');

            return false;
        }

        return $this->runProcess->handle(self::INSTALLER, $write) && $this->isInstalled();
    }
}
