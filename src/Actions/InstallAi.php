<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Closure;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Outcome;
use PaoloBellini\LaravelPreset\Enums\Package;

final readonly class InstallAi {
    private const STUB = 'ai/dot-ai';

    private const DIRECTORY = '.ai';

    private const BOOST = 'laravel/boost';

    public function __construct(
        private CopyStubDirectory $copyStubDirectory,
        private RemoveOtherAgents $removeOtherAgents,
        private PinBoostAgent $pinBoostAgent,
        private RequireDependencies $requireDependencies,
    ) {}

    /**
     * @param  array<int, Package>  $packages
     * @param  Closure(string): void  $write
     * @return array<string, Outcome>
     */
    public function handle(ResolvedRuntime $resolved, array $packages, bool $force, bool $noInstall, Closure $write): array {
        $withoutPackage = [];

        foreach (Package::cases() as $package) {
            $guideline = $package->guideline();

            if ($guideline !== null && ! in_array($package, $packages, true)) {
                $withoutPackage[] = $guideline;
            }
        }

        return [
            ...$this->copyStubDirectory->handle(self::STUB, self::DIRECTORY, $force, $withoutPackage),
            ...$this->removeOtherAgents->handle(),
            PinBoostAgent::FILE => $this->pinBoostAgent->handle(),
            ...$this->requireDependencies->handle($resolved, [self::BOOST], true, $force, $noInstall, $write),
        ];
    }
}
