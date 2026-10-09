<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Data;

use PaoloBellini\LaravelPreset\Enums\Runtime;

final readonly class ResolvedRuntime {
    public function __construct(
        public Runtime $runtime,
        public bool $insideContainer,
    ) {}
}
