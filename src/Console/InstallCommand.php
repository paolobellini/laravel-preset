<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\multiselect;

final class InstallCommand extends Command
{
    protected $signature = 'preset:install
        {--configs : Install lint/format/static-analysis configs and their dependencies}
        {--ai : Install the .ai conventions and guidelines}
        {--scripts : Install composer quality scripts}
        {--github : Install GitHub Actions workflows (calls the bellini.one reusable workflows)}
        {--lefthook : Install the lefthook pre-commit config (opt-in)}
        {--force : Overwrite files that already exist}
        {--no-install : Write the new dependencies into composer.json without installing them}';

    protected $description = 'Scaffold the personal Laravel preset: tooling configs, conventions, scripts and CI.';

    /**
     * Config stub => destination path, relative to the project root.
     *
     * @var array<string, string>
     */
    private const CONFIG_FILES = [
        'configs/pint.json' => 'pint.json',
        'configs/phpstan.neon' => 'phpstan.neon',
        'configs/rector.php' => 'rector.php',
        'configs/essentials.php' => 'config/essentials.php',
    ];

    /**
     * Lefthook stub => destination path, relative to the project root. Opt-in,
     * so it is not part of self::CONFIG_FILES.
     */
    private const LEFTHOOK_STUB = 'configs/lefthook.yml';

    private const LEFTHOOK_FILE = 'lefthook.yml';

    /**
     * AI stub directory => destination directory, relative to the project root.
     *
     * @var array<string, string>
     */
    private const AI_DIRS = [
        'ai/dot-ai' => '.ai',
    ];

    /**
     * GitHub stub directory => destination directory, relative to the project root.
     *
     * @var array<string, string>
     */
    private const GITHUB_DIRS = [
        'github' => '.github',
    ];

    /**
     * Sail binary, relative to the project root.
     */
    private const SAIL_BINARY = 'vendor/bin/sail';

    /**
     * Starter-kit workflows superseded by the preset, removed before copying.
     *
     * @var array<int, string>
     */
    private const SUPERSEDED_WORKFLOWS = [
        '.github/workflows/lint.yml',
        '.github/workflows/tests.yml',
    ];

    /**
     * Packages added to "require". Intentionally unconstrained: the constraint is
     * resolved by composer at install time, so a fresh install always gets the
     * latest stable release instead of a constraint frozen in this package.
     *
     * @var array<int, string>
     */
    private const COMPOSER_REQUIRE = [
        'nunomaduro/essentials',
        'spatie/laravel-data',
        'spatie/laravel-query-builder',
        'thecodingmachine/safe',
    ];

    /**
     * Packages added to "require-dev". Unconstrained, see self::COMPOSER_REQUIRE.
     *
     * @var array<int, string>
     */
    private const COMPOSER_REQUIRE_DEV = [
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
        'spatie/laravel-typescript-transformer',
        'thecodingmachine/phpstan-safe-rule',
    ];

    /**
     * Plugins the preset dependencies need allowed to run.
     *
     * @var array<int, string>
     */
    private const ALLOWED_PLUGINS = [
        'pestphp/pest-plugin',
    ];

    /**
     * @var array<string, string|array<int, string>>
     */
    private const COMPOSER_SCRIPTS = [
        'lint' => 'pint --parallel',
        'tia' => 'pest --tia',
        'type' => 'pest --type-coverage --min=90 --memory-limit=2G',
        'coverage' => 'pest --coverage --min=90',
        'refactor' => 'rector',
        'analyse' => 'phpstan analyse --memory-limit=2G',
        'check:lint' => 'pint --parallel --test',
        'check:refactor' => 'rector --dry-run',
        'tests' => ['@type', '@coverage'],
        'php-checks' => ['@check:lint', '@analyse', '@check:refactor'],
        'node-checks' => ['npm run lint:check', 'npm run format:check', 'npm run types:check'],
        'cleanup' => ['@lint', '@tests', '@analyse', '@check:refactor'],
    ];

    public function handle(Filesystem $files): int
    {
        $groups = $this->resolveGroups();

        if ($groups === []) {
            $this->components->warn('Nothing selected. Aborting.');

            return self::SUCCESS;
        }

        if (in_array('configs', $groups, true)) {
            $this->installConfigs($files);
        }

        if (in_array('ai', $groups, true)) {
            $this->installAi($files);
        }

        if (in_array('scripts', $groups, true)) {
            $this->installScripts($files);
        }

        if (in_array('github', $groups, true)) {
            $this->installGithub($files);
        }

        if (in_array('lefthook', $groups, true)) {
            $this->installLefthook($files);
        }

        $this->newLine();
        $this->components->info('Preset installed.');
        $this->components->bulletList(array_values(array_filter([
            $this->option('no-install') ? 'Run <fg=cyan>composer update</> to pull the new PHP dependencies.' : null,
            'Run <fg=cyan>composer cleanup</> to verify everything passes.',
            in_array('lefthook', $groups, true)
                ? 'Run <fg=cyan>lefthook install</> to wire up the git hooks.'
                : null,
        ])));

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function runComposer(Filesystem $files, array $arguments): bool
    {
        $binary = $this->usesSail($files) ? './'.self::SAIL_BINARY.' composer' : 'composer';
        $command = $binary.' '.implode(' ', $arguments);

        $this->newLine();
        $this->components->info("Running {$command}…");

        $result = Process::path($this->laravel->basePath())
            ->forever()
            ->run($command, function (string $type, string $output): void {
                $this->output->write($output);
            });

        if (! $result->successful()) {
            $this->components->error("{$command} failed — run it manually.");

            return false;
        }

        return true;
    }

    private function usesSail(Filesystem $files): bool
    {
        if (! $files->exists($this->basePath(self::SAIL_BINARY))) {
            return false;
        }

        return $files->exists($this->basePath('compose.yaml'))
            || $files->exists($this->basePath('docker-compose.yml'));
    }

    /**
     * @return array<int, string>
     */
    private function resolveGroups(): array
    {
        $available = ['configs', 'ai', 'scripts', 'github', 'lefthook'];

        // Lefthook is opt-in: offered, but never selected unless asked for.
        $default = ['configs', 'ai', 'scripts', 'github'];

        $flagged = array_values(array_filter(
            $available,
            fn (string $group): bool => (bool) $this->option($group),
        ));

        if ($flagged !== []) {
            return $flagged;
        }

        if (! $this->input->isInteractive()) {
            return $default;
        }

        /** @var array<int, string> $selected */
        $selected = multiselect(
            label: 'Which parts of the preset should be installed?',
            options: [
                'configs' => 'Lint / format / static-analysis configs + dependencies',
                'ai' => 'The .ai conventions and guidelines',
                'scripts' => 'Composer quality scripts',
                'github' => 'GitHub Actions workflows (bellini.one reusable workflows)',
                'lefthook' => 'Lefthook pre-commit hooks (requires the lefthook binary)',
            ],
            default: $default,
            required: true,
        );

        return $selected;
    }

    private function installConfigs(Filesystem $files): void
    {
        $this->components->task('Copying tooling configs', function () use ($files): void {
            foreach (self::CONFIG_FILES as $stub => $destination) {
                $this->copyFile($files, $stub, $destination);
            }
        });
    }

    private function installLefthook(Filesystem $files): void
    {
        $this->components->task('Copying the lefthook config', function () use ($files): void {
            if ($this->copyFile($files, self::LEFTHOOK_STUB, self::LEFTHOOK_FILE)) {
                $this->stripSailFromLefthook($files, self::LEFTHOOK_FILE);
            }
        });
    }

    private function installAi(Filesystem $files): void
    {
        $this->components->task('Copying .ai conventions', function () use ($files): void {
            foreach (self::AI_DIRS as $stub => $destination) {
                $this->copyDirectory($files, $stub, $destination);
            }
        });
    }

    private function installGithub(Filesystem $files): void
    {
        $this->components->task('Removing superseded starter-kit workflows', function () use ($files): void {
            foreach (self::SUPERSEDED_WORKFLOWS as $workflow) {
                $target = $this->basePath($workflow);

                if ($files->exists($target)) {
                    $files->delete($target);
                    $this->line("  <fg=yellow>removed</> {$workflow}");
                }
            }
        });

        $this->components->task('Copying GitHub Actions workflows', function () use ($files): void {
            foreach (self::GITHUB_DIRS as $stub => $destination) {
                $this->copyDirectory($files, $stub, $destination);
            }
        });
    }

    private function installScripts(Filesystem $files): void
    {
        $this->components->task('Patching composer.json', fn () => $this->patchComposerJson($files));

        $this->requireDependencies($files);
    }

    /**
     * Add the preset dependencies with `composer require`, letting composer resolve
     * the newest stable constraint for each package.
     */
    private function requireDependencies(Filesystem $files): void
    {
        $path = $this->basePath('composer.json');

        if (! $files->exists($path)) {
            return;
        }

        /** @var array<string, mixed> $composer */
        $composer = json_decode($files->get($path), true);

        /** @var array<string, string> $require */
        $require = $composer['require'] ?? [];
        /** @var array<string, string> $requireDev */
        $requireDev = $composer['require-dev'] ?? [];

        $missing = $this->missingPackages(self::COMPOSER_REQUIRE, $require + $requireDev);
        $missingDev = $this->missingPackages(self::COMPOSER_REQUIRE_DEV, $require + $requireDev);

        if ($missing === [] && $missingDev === []) {
            $this->line('  <fg=yellow>skipped</> composer require (all preset dependencies already present)');

            return;
        }

        // --no-update still writes the latest constraint, it only skips the install.
        $flags = ['--no-interaction'];

        if ($this->option('no-install')) {
            $flags[] = '--no-update';
        }

        if ($missing !== []) {
            $this->runComposer($files, ['require', ...$flags, ...$missing]);
        }

        if ($missingDev !== []) {
            $this->runComposer($files, ['require', '--dev', ...$flags, ...$missingDev]);
        }
    }

    /**
     * @param  array<int, string>  $packages
     * @param  array<string, string>  $installed
     * @return array<int, string>
     */
    private function missingPackages(array $packages, array $installed): array
    {
        if ($this->option('force')) {
            return $packages;
        }

        return array_values(array_filter(
            $packages,
            fn (string $package): bool => ! array_key_exists($package, $installed),
        ));
    }

    private function copyFile(Filesystem $files, string $stub, string $destination): bool
    {
        $target = $this->basePath($destination);
        $source = $this->stubPath($stub);

        if ($files->exists($target) && ! $this->option('force')) {
            $this->line("  <fg=yellow>skipped</> {$destination} (exists, use --force)");

            return false;
        }

        $files->ensureDirectoryExists(dirname($target));
        $files->copy($source, $target);

        return true;
    }

    /**
     * The stub drives the hooks through Sail. Without Sail the commands run
     * directly, so the prefix is stripped from the copied file.
     */
    private function stripSailFromLefthook(Filesystem $files, string $destination): void
    {
        if ($this->usesSail($files)) {
            return;
        }

        $target = $this->basePath($destination);

        $files->put($target, str_replace(
            self::SAIL_BINARY.' composer ',
            'composer ',
            $files->get($target),
        ));

        $this->line('  <fg=yellow>adjusted</> '.$destination.' (sail not detected, using plain composer)');
    }

    private function copyDirectory(Filesystem $files, string $stub, string $destination): void
    {
        $source = $this->stubPath($stub);
        $target = $this->basePath($destination);

        foreach ($files->allFiles($source) as $file) {
            $relative = $file->getRelativePathname();
            $fileTarget = $target.DIRECTORY_SEPARATOR.$relative;

            if ($files->exists($fileTarget) && ! $this->option('force')) {
                continue;
            }

            $files->ensureDirectoryExists(dirname($fileTarget));
            $files->copy($file->getPathname(), $fileTarget);
        }
    }

    private function patchComposerJson(Filesystem $files): void
    {
        $path = $this->basePath('composer.json');

        if (! $files->exists($path)) {
            $this->line('  <fg=yellow>skipped</> composer.json (not found)');

            return;
        }

        /** @var array<string, mixed> $composer */
        $composer = json_decode($files->get($path), true);

        $composer['scripts'] = array_merge($composer['scripts'] ?? [], self::COMPOSER_SCRIPTS);
        $composer['config'] = $this->allowPlugins($composer['config'] ?? []);

        $files->put($path, $this->encodeJson($composer));
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function allowPlugins(array $config): array
    {
        /** @var array<string, bool> $allowed */
        $allowed = $config['allow-plugins'] ?? [];

        foreach (self::ALLOWED_PLUGINS as $plugin) {
            $allowed[$plugin] = true;
        }

        ksort($allowed);

        $config['allow-plugins'] = $allowed;

        return $config;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function encodeJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
    }

    private function basePath(string $path): string
    {
        return $this->laravel->basePath($path);
    }

    private function stubPath(string $path): string
    {
        return dirname(__DIR__, 2).'/stubs/'.$path;
    }
}
