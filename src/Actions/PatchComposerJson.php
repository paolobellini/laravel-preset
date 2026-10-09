<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class PatchComposerJson {
    public const FILE = 'composer.json';

    private const CLEAR_CONFIG = '@php artisan config:clear --ansi @no_additional_args';

    /**
     * @var array<string, string|array<int, string>>
     */
    private const SCRIPTS = [
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

    /**
     * @var array<int, string>
     */
    private const ALLOWED_PLUGINS = [
        'pestphp/pest-plugin',
    ];

    public function __construct(
        private Application $app,
        private Filesystem $files,
    ) {}

    public function handle(): Outcome {
        $path = $this->app->basePath(self::FILE);

        if (! $this->files->exists($path)) {
            return Outcome::Skipped;
        }

        /** @var array<string, mixed> $composer */
        $composer = json_decode($this->files->get($path), true);

        /** @var array<string, bool> $allowed */
        $allowed = $composer['config']['allow-plugins'] ?? [];

        foreach (self::ALLOWED_PLUGINS as $plugin) {
            $allowed[$plugin] = true;
        }

        ksort($allowed);

        $composer['scripts'] = array_merge($composer['scripts'] ?? [], self::SCRIPTS);
        $composer['config']['allow-plugins'] = $allowed;

        $this->files->put(
            $path,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
        );

        return Outcome::Patched;
    }
}
