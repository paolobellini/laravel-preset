<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class InstallGithub {
    private const STUB = 'github';

    private const DIRECTORY = '.github';

    /**
     * @var array<int, string>
     */
    private const SUPERSEDED_WORKFLOWS = [
        '.github/workflows/lint.yml',
        '.github/workflows/tests.yml',
    ];

    /**
     * @var array<string, string>
     */
    private const SHARDED_FILES = [
        'sharded/tests.yml' => '.github/workflows/tests.yml',
        'sharded/update-shards.yml' => '.github/workflows/update-shards.yml',
    ];

    private const DEPENDABOT_FILE = '.github/dependabot.yml';

    private const RENOVATE_STUB = 'renovate/renovate.json';

    private const RENOVATE_FILE = '.github/renovate.json';

    private const RENOVATE_WORKFLOW_STUB = 'renovate/renovate-workflow.yml';

    private const RENOVATE_WORKFLOW_FILE = '.github/workflows/renovate.yml';

    public function __construct(
        private CopyStub $copyStub,
        private CopyStubDirectory $copyStubDirectory,
        private RemovePath $removePath,
        private CreateVexDocument $createVexDocument,
    ) {}

    /**
     * @return array<string, Outcome>
     */
    public function handle(bool $force, bool $sharded, bool $renovate, bool $renovateSelfHosted): array {
        $outcomes = [];

        foreach (self::SUPERSEDED_WORKFLOWS as $workflow) {
            if ($this->removePath->handle($workflow)) {
                $outcomes[$workflow] = Outcome::Removed;
            }
        }

        $outcomes = [
            ...$outcomes,
            ...$this->copyStubDirectory->handle(self::STUB, self::DIRECTORY, $force),
            CreateVexDocument::FILE => $this->createVexDocument->handle($force),
        ];

        if ($sharded) {
            foreach (self::SHARDED_FILES as $stub => $destination) {
                $this->copyStub->handle($stub, $destination, force: true);
                $outcomes[$destination] = Outcome::Created;
            }
        }

        if ($renovate || $renovateSelfHosted) {
            $this->copyStub->handle(self::RENOVATE_STUB, self::RENOVATE_FILE, force: true);
            $this->removePath->handle(self::DEPENDABOT_FILE);

            $outcomes[self::RENOVATE_FILE] = Outcome::Created;
            $outcomes[self::DEPENDABOT_FILE] = Outcome::Removed;
        }

        if ($renovateSelfHosted) {
            $this->copyStub->handle(self::RENOVATE_WORKFLOW_STUB, self::RENOVATE_WORKFLOW_FILE, force: true);
            $outcomes[self::RENOVATE_WORKFLOW_FILE] = Outcome::Created;
        }

        return $outcomes;
    }
}
