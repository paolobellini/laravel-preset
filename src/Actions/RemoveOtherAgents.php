<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class RemoveOtherAgents {
    /**
     * @var array<int, string>
     */
    private const ARTEFACTS = [
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

    public function __construct(private RemovePath $removePath) {}

    /**
     * @return array<string, Outcome>
     */
    public function handle(): array {
        $outcomes = [];

        foreach (self::ARTEFACTS as $artefact) {
            if ($this->removePath->handle($artefact)) {
                $outcomes[$artefact] = Outcome::Removed;
            }
        }

        return $outcomes;
    }
}
