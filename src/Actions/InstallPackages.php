<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Closure;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Outcome;
use PaoloBellini\LaravelPreset\Enums\Package;

final readonly class InstallPackages {
    /**
     * @var array<int, string>
     */
    public const MANDATORY = [
        'nunomaduro/essentials',
        'thecodingmachine/safe',
    ];

    private const ESSENTIALS_CONFIG = 'config/essentials.php';

    public function __construct(
        private CopyStub $copyStub,
        private RequireDependencies $requireDependencies,
    ) {}

    /**
     * @param  array<int, Package>  $packages
     * @param  Closure(string): void  $write
     * @return array<string, Outcome>
     */
    public function handle(ResolvedRuntime $resolved, array $packages, bool $force, bool $noInstall, Closure $write): array {
        $names = self::MANDATORY;

        foreach ($packages as $package) {
            $names[] = $package->composerName();
        }

        sort($names);

        $copied = $this->copyStub->handle('configs/essentials.php', self::ESSENTIALS_CONFIG, $force);

        return [
            self::ESSENTIALS_CONFIG => $copied ? Outcome::Created : Outcome::Skipped,
            ...$this->requireDependencies->handle($resolved, $names, false, $force, $noInstall, $write),
        ];
    }
}
