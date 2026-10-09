<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Data;

final readonly class Environment {
    public function __construct(
        public bool $sailConfigured,
        public bool $insideContainer,
    ) {}
}
