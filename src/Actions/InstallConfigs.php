<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class InstallConfigs {
    /**
     * @var array<string, string>
     */
    private const FILES = [
        'configs/pint.json' => 'pint.json',
        'configs/phpstan.neon' => 'phpstan.neon',
        'configs/rector.php' => 'rector.php',
        'configs/rector-tests.php' => 'rector-tests.php',
        'configs/psalm.xml' => 'psalm.xml',
    ];

    public function __construct(
        private CopyStub $copyStub,
        private ConfigurePest $configurePest,
    ) {}

    /**
     * @return array<string, Outcome>
     */
    public function handle(bool $force): array {
        $outcomes = [];

        foreach (self::FILES as $stub => $destination) {
            $outcomes[$destination] = $this->copyStub->handle($stub, $destination, $force)
                ? Outcome::Created
                : Outcome::Skipped;
        }

        $outcomes[ConfigurePest::FILE] = $this->configurePest->handle();

        return $outcomes;
    }
}
