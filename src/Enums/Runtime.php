<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Enums;

use PaoloBellini\LaravelPreset\Data\Environment;

enum Runtime: string {
    case Sail = 'sail';
    case Local = 'local';

    public const SAIL_BINARY = 'vendor/bin/sail';

    public static function suggestedFor(Environment $environment): self {
        return $environment->sailConfigured ? self::Sail : self::Local;
    }

    public function label(): string {
        return match ($this) {
            self::Sail => 'Laravel Sail',
            self::Local => 'Local',
        };
    }
}
