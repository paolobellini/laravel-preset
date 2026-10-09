<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Exceptions;

use InvalidArgumentException;
use PaoloBellini\LaravelPreset\Actions\WritePreset;

final class InvalidPreset extends InvalidArgumentException {
    public static function malformed(): self {
        return new self(WritePreset::FILE.' could not be read: fix it or delete it to start over.');
    }

    public static function unknown(string $key, string $value): self {
        return new self(WritePreset::FILE." holds an unknown {$key} [{$value}]: fix it or delete it to start over.");
    }
}
