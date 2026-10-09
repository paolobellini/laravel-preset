<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Runtime;

final readonly class BuildCommand {
    public function handle(ResolvedRuntime $resolved, string $command): string {
        if ($resolved->runtime === Runtime::Sail && ! $resolved->insideContainer) {
            return './'.Runtime::SAIL_BINARY.' '.$command;
        }

        return $command;
    }
}
