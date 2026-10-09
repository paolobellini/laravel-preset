<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Exceptions;

use InvalidArgumentException;
use PaoloBellini\LaravelPreset\Enums\Runtime;

final class InvalidRuntime extends InvalidArgumentException {
    public static function unknown(string $runtime): self {
        $known = implode(', ', array_column(Runtime::cases(), 'value'));

        return new self("Unknown runtime [{$runtime}]. Use one of: {$known}.");
    }

    public static function sailNotConfigured(): self {
        return new self(
            'The sail runtime needs Laravel Sail: '.Runtime::SAIL_BINARY.' and a compose file were not both found.'
        );
    }
}
