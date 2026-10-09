<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

final class InstallCommand extends Command {
    protected $signature = 'preset:install
        {--configs : Install lint/format/static-analysis configs and their dependencies}
        {--ai : Install the .ai conventions and guidelines}
        {--scripts : Install composer quality scripts}
        {--github : Install GitHub Actions workflows and the dependency update config}
        {--lefthook : Install the lefthook pre-commit config (opt-in)}
        {--skills : Install the agent skills into .agents/skills (opt-in)}
        {--codegraph : Build the CodeGraph index for this project (opt-in)}
        {--sharded : Replace the tests workflow with the sharded matrix variant}
        {--renovate : Use renovate instead of dependabot for dependency updates}
        {--renovate-selfhosted : Also install the scheduled workflow that runs renovate without the GitHub App}
        {--force : Overwrite files that already exist}
        {--no-install : Write the new dependencies into composer.json without installing them}';

    protected $description = 'Scaffold the personal Laravel preset: tooling configs, conventions, scripts and CI.';

    /**
     * @var array<string, string>
     */
    private const CONFIG_FILES = [
        'configs/pint.json' => 'pint.json',
        'configs/phpstan.neon' => 'phpstan.neon',
        'configs/rector.php' => 'rector.php',
        'configs/rector-tests.php' => 'rector-tests.php',
        'configs/essentials.php' => 'config/essentials.php',
        'configs/psalm.xml' => 'psalm.xml',
    ];

    private const LEFTHOOK_STUB = 'configs/lefthook.yml';

    private const LEFTHOOK_FILE = 'lefthook.yml';

    private const PEST_STUB = 'configs/pest.php';

    private const PEST_FILE = 'tests/Pest.php';

    private const PEST_TIA = <<<'PHP'

        pest()->tia()->locally();

        PHP;

    /**
     * @var array<string, string>
     */
    private const AI_DIRS = [
        'ai/dot-ai' => '.ai',
    ];

    /**
     * @var array<string, string>
     */
    private const GITHUB_DIRS = [
        'github' => '.github',
    ];

    /**
     * @var array<string, string>
     */
    private const SHARDED_FILES = [
        'sharded/tests.yml' => '.github/workflows/tests.yml',
        'sharded/update-shards.yml' => '.github/workflows/update-shards.yml',
    ];

    private const VEX_STUB = 'vex/openvex.json';

    private const VEX_FILE = '.vex/openvex.json';

    private const RENOVATE_STUB = 'renovate/renovate.json';

    private const RENOVATE_FILE = '.github/renovate.json';

    private const DEPENDABOT_FILE = '.github/dependabot.yml';

    private const RENOVATE_WORKFLOW_STUB = 'renovate/renovate-workflow.yml';

    private const RENOVATE_WORKFLOW_FILE = '.github/workflows/renovate.yml';

    private const SAIL_BINARY = 'vendor/bin/sail';

    private const SAIL_ENV = 'LARAVEL_SAIL';

    /**
     * Agent scaffolding written by boost:install for agents other than Claude Code.
     * `.idea` and `.vscode` are deliberately absent: boost writes into them, but
     * they belong to the editor, not to boost.
     *
     * @var array<int, string>
     */
    private const SUPERSEDED_AGENTS = [
        '.amp',
        '.codex',
        '.cursor',
        '.factory',
        '.gemini',
        '.grok',
        '.junie',
        '.kiro',
        '.pi',
        '.zed',
        '.github/copilot-instructions.md',
    ];

    /**
     * Source repository => skill names, as declared in each SKILL.md. They fill the
     * gaps the personal guidelines leave rather than restating them, so nothing here
     * competes with a convention the agent already carries.
     *
     * @var array<string, array<int, string>>
     */
    private const SKILLS = [
        'jpcaparas/superpowers-laravel' => [
            'laravel:queues-and-horizon',
            'laravel:http-client-resilience',
            'laravel:performance-select-columns',
        ],
        'mattpocock/skills' => [
            'wait-what',
            'teach',
        ],
    ];

    /**
     * Skills land in .agents/skills as real files; claude-code gets symlinks to them.
     * Naming the agents keeps the installer from writing into the directories of the
     * agents self::SUPERSEDED_AGENTS removes.
     *
     * @var array<int, string>
     */
    private const SKILL_AGENTS = ['universal', 'claude-code'];

    private const CODEGRAPH_BINARY = 'codegraph';

    private const CODEGRAPH_DIR = '.codegraph';

    private const CODEGRAPH_INSTALLER = 'npm install --global @colbymchenry/codegraph';

    private const CODEGRAPH_WIRE = 'codegraph install --yes --target claude --location local';

    private const CODEGRAPH_INIT = 'codegraph init --yes';

    private const BOOST_CONFIG = 'boost.json';

    private const BOOST_AGENT = 'claude_code';

    /**
     * @var array<int, string>
     */
    private const SUPERSEDED_WORKFLOWS = [
        '.github/workflows/lint.yml',
        '.github/workflows/tests.yml',
    ];

    /**
     * @var array<int, string>
     */
    private const COMPOSER_REQUIRE = [
        'nunomaduro/essentials',
        'spatie/laravel-data',
        'spatie/laravel-query-builder',
        'spatie/laravel-typescript-transformer',
        'thecodingmachine/safe',
    ];

    /**
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
        'thecodingmachine/phpstan-safe-rule',
        'vimeo/psalm',
    ];

    /**
     * @var array<int, string>
     */
    private const ALLOWED_PLUGINS = [
        'pestphp/pest-plugin',
    ];

    private const CLEAR_CONFIG = '@php artisan config:clear --ansi @no_additional_args';

    /**
     * @var array<string, string|array<int, string>>
     */
    private const COMPOSER_SCRIPTS = [
        'pint' => 'pint --parallel',
        'pint:dry' => 'pint --parallel --test',
        'rector' => 'rector',
        'rector:test' => 'rector --config=rector-tests.php',
        'rector:dry' => 'rector --dry-run',
        'rector:test:dry' => 'rector --config=rector-tests.php --dry-run',
        'stan' => 'phpstan analyse --memory-limit=-1',
        'taint' => 'psalm --taint-analysis --no-cache',
        'test' => [self::CLEAR_CONFIG, 'pest --parallel'],
        'test:type-coverage' => [self::CLEAR_CONFIG, 'pest --parallel --type-coverage --min=95 --memory-limit=-1'],
        'test:coverage' => [self::CLEAR_CONFIG, 'pest --parallel --coverage --min=90'],
        'test:mutate' => [
            'Composer\\Config::disableProcessTimeout',
            self::CLEAR_CONFIG,
            'pest --parallel --mutate --covered-only --min=65',
        ],
        'update-shards' => [self::CLEAR_CONFIG, 'pest --parallel --update-shards'],

        'analyse:static' => ['@pint:dry', '@stan', '@rector:dry'],
        'analyse' => ['@analyse:static', '@taint'],
        'tests' => ['@test:type-coverage', '@test:coverage'],
        'ai:cleanup' => ['@stan', '@rector:dry', '@test:coverage'],

        'pre-commit' => ['@analyse:static', '@test', '@ci:node'],
        'pre-push' => ['@rector:test:dry', '@test:mutate'],
        'ci:node' => ['npm run lint:check', 'npm run format:check', 'npm run types:check'],
        'ci:php' => ['@analyse', '@tests'],
        'ci' => ['@ci:php', '@ci:node'],
    ];

    public function handle(Filesystem $files): int {
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

        if (in_array('skills', $groups, true)) {
            $this->installSkills();
        }

        if (in_array('codegraph', $groups, true)) {
            $this->installCodegraph($files);
        }

        $this->newLine();
        $this->components->info('Preset installed.');
        $this->components->bulletList(array_values(array_filter([
            $this->option('no-install') ? 'Run <fg=cyan>composer update</> to pull the new PHP dependencies.' : null,
            'Run <fg=cyan>composer cleanup</> to verify everything passes.',
            in_array('lefthook', $groups, true)
                ? 'Run <fg=cyan>lefthook install</> to wire up the git hooks.'
                : null,
            in_array('skills', $groups, true)
                ? 'Commit <fg=cyan>.agents/skills</> and <fg=cyan>skills-lock.json</>; <fg=cyan>npx skills update</> refreshes them.'
                : null,
            in_array('ai', $groups, true)
                ? 'Run <fg=cyan>php artisan boost:install</> — <fg=cyan>boost.json</> already pins it to Claude Code.'
                : null,
        ])));

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function runComposer(Filesystem $files, array $arguments): bool {
        $binary = $this->usesSail($files) && ! $this->insideSail()
            ? './'.self::SAIL_BINARY.' composer'
            : 'composer';
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

    private function usesSail(Filesystem $files): bool {
        if (! $files->exists($this->basePath(self::SAIL_BINARY))) {
            return false;
        }

        return $files->exists($this->basePath('compose.yaml'))
            || $files->exists($this->basePath('docker-compose.yml'));
    }

    private function insideSail(): bool {
        return (bool) getenv(self::SAIL_ENV);
    }

    /**
     * @return array<int, string>
     */
    private function resolveGroups(): array {
        $available = ['configs', 'ai', 'scripts', 'github', 'lefthook', 'skills', 'codegraph'];

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
                'github' => 'GitHub Actions workflows + dependency updates',
                'lefthook' => 'Lefthook pre-commit hooks (requires the lefthook binary)',
                'skills' => 'Agent skills for queues, transactions, scheduling, … (requires npx)',
                'codegraph' => 'CodeGraph index for this project (requires the codegraph binary)',
            ],
            default: $default,
            required: true,
        );

        return $selected;
    }

    private function installConfigs(Filesystem $files): void {
        $this->components->task('Copying tooling configs', function () use ($files): void {
            foreach (self::CONFIG_FILES as $stub => $destination) {
                $this->copyFile($files, $stub, $destination);
            }
        });

        $this->components->task('Configuring Pest', fn () => $this->configurePest($files));
    }

    private function configurePest(Filesystem $files): void {
        $target = $this->basePath(self::PEST_FILE);

        if (! $files->exists($target)) {
            $this->copyFile($files, self::PEST_STUB, self::PEST_FILE);

            return;
        }

        $contents = $files->get($target);

        if (str_contains($contents, '->tia(')) {
            $this->line('  <fg=yellow>skipped</> '.self::PEST_FILE.' (already configures tia)');

            return;
        }

        $files->put($target, rtrim($contents, PHP_EOL).PHP_EOL.self::PEST_TIA);
        $this->line('  <fg=green>patched</> '.self::PEST_FILE.' (tia enabled locally)');
    }

    private function installLefthook(Filesystem $files): void {
        $this->components->task('Copying the lefthook config', function () use ($files): void {
            if ($this->copyFile($files, self::LEFTHOOK_STUB, self::LEFTHOOK_FILE)) {
                $this->stripSailFromLefthook($files, self::LEFTHOOK_FILE);
            }
        });
    }

    private function installAi(Filesystem $files): void {
        $this->components->task('Copying .ai conventions', function () use ($files): void {
            foreach (self::AI_DIRS as $stub => $destination) {
                $this->copyDirectory($files, $stub, $destination);
            }
        });

        $this->components->task('Removing other agents', fn () => $this->removeOtherAgents($files));
    }

    /**
     * Delete the scaffolding boost:install wrote for every agent but Claude Code, and
     * pin the choice in boost.json. Without the pin, boost re-detects agents from what
     * it finds on the machine — PhpStorm being installed is enough — and writes the
     * directories again on the next run.
     */
    private function removeOtherAgents(Filesystem $files): void {
        foreach (self::SUPERSEDED_AGENTS as $artefact) {
            $target = $this->basePath($artefact);

            if ($files->isDirectory($target)) {
                $files->deleteDirectory($target);
                $this->line("  <fg=yellow>removed</> {$artefact}");
            } elseif ($files->exists($target)) {
                $files->delete($target);
                $this->line("  <fg=yellow>removed</> {$artefact}");
            }
        }

        $this->pinBoostAgent($files);
    }

    private function pinBoostAgent(Filesystem $files): void {
        $path = $this->basePath(self::BOOST_CONFIG);

        /** @var array<string, mixed> $config */
        $config = $files->exists($path) ? (json_decode($files->get($path), true) ?? []) : [];

        if (($config['agents'] ?? null) === [self::BOOST_AGENT]) {
            return;
        }

        $config['agents'] = [self::BOOST_AGENT];
        ksort($config);

        $files->put($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        $this->line('  <fg=green>pinned</> '.self::BOOST_CONFIG.' to claude_code');
    }

    /**
     * Wires CodeGraph into the project (`.mcp.json`, `.claude/`) and builds the
     * index. The binary lives on the host, once per machine: from inside the Sail
     * container it is neither visible nor worth installing.
     */
    private function installCodegraph(Filesystem $files): void {
        if ($this->insideSail()) {
            $this->components->warn(
                'CodeGraph runs on the host, not in the Sail container. '
                .'Run `php artisan preset:install --codegraph` outside Sail.'
            );

            return;
        }

        if (! $this->hasCodegraph() && ! $this->setUpCodegraph()) {
            return;
        }

        if (! $this->runProcess(self::CODEGRAPH_WIRE)) {
            return;
        }

        if ($files->isDirectory($this->basePath(self::CODEGRAPH_DIR)) && ! $this->option('force')) {
            $this->components->task('Building the CodeGraph index', function (): bool {
                $this->line('  <fg=yellow>skipped</> '.self::CODEGRAPH_DIR.' (exists, use --force)');

                return true;
            });

            return;
        }

        $this->runProcess(self::CODEGRAPH_INIT);
    }

    /**
     * The binary is the one thing that lands outside the project, so installing
     * it is offered rather than run.
     */
    private function setUpCodegraph(): bool {
        if (! $this->input->isInteractive()) {
            $this->components->warn(
                'CodeGraph is not on your PATH. Install it with `'.self::CODEGRAPH_INSTALLER.'`, then run this again.'
            );

            return false;
        }

        if (! confirm(label: 'CodeGraph is not installed. Install it now?', default: false)) {
            $this->components->warn('Skipped. Run `'.self::CODEGRAPH_INSTALLER.'` when you want it.');

            return false;
        }

        return $this->runProcess(self::CODEGRAPH_INSTALLER) && $this->hasCodegraph();
    }

    private function hasCodegraph(): bool {
        return Process::path($this->laravel->basePath())
            ->run('command -v '.self::CODEGRAPH_BINARY)
            ->successful();
    }

    private function installSkills(): void {
        foreach (self::SKILLS as $source => $skills) {
            $arguments = ['add', $source];

            foreach ($skills as $skill) {
                $arguments[] = '--skill';
                $arguments[] = $skill;
            }

            foreach (self::SKILL_AGENTS as $agent) {
                $arguments[] = '--agent';
                $arguments[] = $agent;
            }

            $this->runSkills($arguments);
        }
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function runSkills(array $arguments): bool {
        return $this->runProcess('npx --yes skills@latest '.implode(' ', $arguments));
    }

    private function runProcess(string $command): bool {
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

    private function installGithub(Filesystem $files): void {
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

        $this->components->task('Creating the VEX document', function () use ($files): void {
            if ($this->copyFile($files, self::VEX_STUB, self::VEX_FILE)) {
                $this->identifyVex($files);
            }
        });

        if ($this->option('sharded')) {
            $this->components->task('Sharding the tests workflow', function () use ($files): void {
                foreach (self::SHARDED_FILES as $stub => $destination) {
                    $target = $this->basePath($destination);

                    $files->ensureDirectoryExists(dirname($target));
                    $files->copy($this->stubPath($stub), $target);
                }
            });
        }

        if (! $this->option('renovate') && ! $this->option('renovate-selfhosted')) {
            return;
        }

        $this->components->task('Swapping dependabot for renovate', function () use ($files): void {
            $files->copy($this->stubPath(self::RENOVATE_STUB), $this->basePath(self::RENOVATE_FILE));
            $files->delete($this->basePath(self::DEPENDABOT_FILE));
        });

        if (! $this->option('renovate-selfhosted')) {
            return;
        }

        $this->components->task('Installing the self-hosted renovate workflow', function () use ($files): void {
            $files->copy($this->stubPath(self::RENOVATE_WORKFLOW_STUB), $this->basePath(self::RENOVATE_WORKFLOW_FILE));
        });
    }

    private function installScripts(Filesystem $files): void {
        $this->components->task('Patching composer.json', fn () => $this->patchComposerJson($files));

        $this->requireDependencies($files);
    }

    private function requireDependencies(Filesystem $files): void {
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

        $missing = $this->missingPackages(self::COMPOSER_REQUIRE, $require);
        $missingDev = $this->missingPackages(self::COMPOSER_REQUIRE_DEV, $require + $requireDev);

        if ($missing === [] && $missingDev === []) {
            $this->line('  <fg=yellow>skipped</> composer require (all preset dependencies already present)');

            return;
        }

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
    private function missingPackages(array $packages, array $installed): array {
        if ($this->option('force')) {
            return $packages;
        }

        return array_values(array_filter(
            $packages,
            fn (string $package): bool => ! array_key_exists($package, $installed),
        ));
    }

    private function copyFile(Filesystem $files, string $stub, string $destination): bool {
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

    private function stripSailFromLefthook(Filesystem $files, string $destination): void {
        if ($this->usesSail($files)) {
            return;
        }

        $target = $this->basePath($destination);

        $files->put($target, str_replace(self::SAIL_BINARY.' ', '', $files->get($target)));

        $this->line('  <fg=yellow>adjusted</> '.$destination.' (sail not detected, running the tools directly)');
    }

    private function copyDirectory(Filesystem $files, string $stub, string $destination): void {
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

    private function identifyVex(Filesystem $files): void {
        $path = $this->basePath(self::VEX_FILE);

        /** @var array<string, mixed> $document */
        $document = json_decode($files->get($path), true);

        $document['@id'] = 'urn:vex:'.str_replace('/', ':', $this->projectName($files));
        $document['timestamp'] = gmdate('Y-m-d\\TH:i:s\\Z');

        $files->put($path, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    private function projectName(Filesystem $files): string {
        $path = $this->basePath('composer.json');

        if ($files->exists($path)) {
            /** @var array<string, mixed> $composer */
            $composer = json_decode($files->get($path), true);

            if (is_string($composer['name'] ?? null) && $composer['name'] !== '') {
                return $composer['name'];
            }
        }

        return basename($this->basePath(''));
    }

    private function patchComposerJson(Filesystem $files): void {
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
    private function allowPlugins(array $config): array {
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
    private function encodeJson(array $data): string {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
    }

    private function basePath(string $path): string {
        return $this->laravel->basePath($path);
    }

    private function stubPath(string $path): string {
        return dirname(__DIR__, 2).'/stubs/'.$path;
    }
}
