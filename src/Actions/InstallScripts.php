<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Closure;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class InstallScripts {
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
            ...$this->requireDependencies->handle($resolved, $force, $noInstall, $write),
        ];
    }
}
