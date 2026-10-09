<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Closure;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class InstallScripts {
    /**
     * @var array<int, string>
     */
    private const DEV_DEPENDENCIES = [
        'driftingly/rector-laravel',
        'fruitcake/laravel-debugbar',
        'larastan/larastan',
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
        private PatchComposerJson $patchComposerJson,
        private RequireDependencies $requireDependencies,
    ) {}

    /**
     * @param  Closure(string): void  $write
     * @return array<string, Outcome>
     */
    public function handle(ResolvedRuntime $resolved, bool $force, bool $noInstall, Closure $write): array {
        return [
            PatchComposerJson::FILE => $this->patchComposerJson->handle(),
            ...$this->requireDependencies->handle($resolved, self::DEV_DEPENDENCIES, true, $force, $noInstall, $write),
        ];
    }
}
