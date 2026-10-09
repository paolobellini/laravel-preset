<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class InstallAi {
    private const STUB = 'ai/dot-ai';

    private const DIRECTORY = '.ai';

    public function __construct(
        private CopyStubDirectory $copyStubDirectory,
        private RemoveOtherAgents $removeOtherAgents,
        private PinBoostAgent $pinBoostAgent,
    ) {}

    /**
     * @return array<string, Outcome>
     */
    public function handle(bool $force): array {
        return [
            ...$this->copyStubDirectory->handle(self::STUB, self::DIRECTORY, $force),
            ...$this->removeOtherAgents->handle(),
            PinBoostAgent::FILE => $this->pinBoostAgent->handle(),
        ];
    }
}
