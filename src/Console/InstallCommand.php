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
        {--force : Overwrite files that already exist}
        {--no-install : Skip the composer update that runs after install}';

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
     * Starter-kit workflows superseded by the preset, removed before copying.
     *
     * @var array<int, string>
     */
    private const SUPERSEDED_WORKFLOWS = [
        '.github/workflows/lint.yml',
        '.github/workflows/tests.yml',
    ];

    /**
     * @var array<string, string>
     */
    private const COMPOSER_REQUIRE = [
        'nunomaduro/essentials' => '^1.2',
    ];

    /**
     * @var array<string, string>
     */
    private const COMPOSER_REQUIRE_DEV = [
        'barryvdh/laravel-ide-helper' => '^3.7',
        'driftingly/rector-laravel' => '^2.5',
        'fruitcake/laravel-debugbar' => '^4.3',
        'larastan/larastan' => '^3.9',
        'laravel/boost' => '^2.2',
        'laravel/pail' => '^1.2.5',
        'laravel/pint' => '^1.27',
        'pestphp/pest' => '^4.7',
        'pestphp/pest-plugin-type-coverage' => '^4.0',
        'rector/rector' => '^2.5',
    ];

    /**
     * @var array<string, string|array<int, string>>
     */
    private const COMPOSER_SCRIPTS = [
        'lint' => 'pint --parallel',
        'type' => 'pest --type-coverage --min=90 --memory-limit=2G',
        'coverage' => 'pest --coverage --min=90',
        'refactor' => 'rector',
        'analyse' => 'phpstan analyse --memory-limit=2G',
        'check:lint' => 'pint --parallel --test',
        'check:refactor' => 'rector --dry-run',
        'ide-helper' => [
            '@php artisan ide-helper:generate',
            '@php artisan ide-helper:models -RW',
        ],
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

        $dependenciesChanged = in_array('scripts', $groups, true);
        $updated = $dependenciesChanged && ! $this->option('no-install') && $this->runComposerUpdate();

        $this->newLine();
        $this->components->info('Preset installed.');
        $this->components->bulletList(array_values(array_filter([
            $updated ? null : 'Run <fg=cyan>composer update</> to pull the new PHP dependencies.',
            'Run <fg=cyan>composer ide-helper</> to generate IDE helpers and model docblocks.',
            'Run <fg=cyan>composer cleanup</> to verify everything passes.',
        ])));

        return self::SUCCESS;
    }

    private function runComposerUpdate(): bool
    {
        $this->newLine();
        $this->components->info('Running composer update…');

        $result = Process::path($this->laravel->basePath())
            ->forever()
            ->run('composer update', function (string $type, string $output): void {
                $this->output->write($output);
            });

        if (! $result->successful()) {
            $this->components->error('composer update failed — run it manually.');

            return false;
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function resolveGroups(): array
    {
        $available = ['configs', 'ai', 'scripts', 'github'];

        $flagged = array_values(array_filter(
            $available,
            fn (string $group): bool => (bool) $this->option($group),
        ));

        if ($flagged !== []) {
            return $flagged;
        }

        if (! $this->input->isInteractive()) {
            return $available;
        }

        /** @var array<int, string> $selected */
        $selected = multiselect(
            label: 'Which parts of the preset should be installed?',
            options: [
                'configs' => 'Lint / format / static-analysis configs + dependencies',
                'ai' => 'The .ai conventions and guidelines',
                'scripts' => 'Composer quality scripts',
                'github' => 'GitHub Actions workflows (bellini.one reusable workflows)',
            ],
            default: $available,
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
    }

    private function copyFile(Filesystem $files, string $stub, string $destination): void
    {
        $target = $this->basePath($destination);
        $source = $this->stubPath($stub);

        if ($files->exists($target) && ! $this->option('force')) {
            $this->line("  <fg=yellow>skipped</> {$destination} (exists, use --force)");

            return;
        }

        $files->ensureDirectoryExists(dirname($target));
        $files->copy($source, $target);
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

        $composer['require'] = $this->mergeDependencies($composer['require'] ?? [], self::COMPOSER_REQUIRE);
        $composer['require-dev'] = $this->mergeDependencies($composer['require-dev'] ?? [], self::COMPOSER_REQUIRE_DEV);
        $composer['scripts'] = array_merge($composer['scripts'] ?? [], self::COMPOSER_SCRIPTS);

        $files->put($path, $this->encodeJson($composer));
    }

    /**
     * @param  array<string, string>  $current
     * @param  array<string, string>  $additions
     * @return array<string, string>
     */
    private function mergeDependencies(array $current, array $additions): array
    {
        foreach ($additions as $name => $constraint) {
            if (! array_key_exists($name, $current) || $this->option('force')) {
                $current[$name] = $constraint;
            }
        }

        ksort($current);

        return $current;
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
