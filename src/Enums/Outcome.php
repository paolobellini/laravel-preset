<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Enums;

enum Outcome: string {
    case Created = 'created';
    case Patched = 'patched';
    case Removed = 'removed';
    case Skipped = 'skipped';
    case Ran = 'ran';
    case Failed = 'failed';

    public function label(): string {
        return match ($this) {
            self::Created => 'created',
            self::Patched => 'patched',
            self::Removed => 'removed',
            self::Skipped => 'skipped',
            self::Ran => 'done',
            self::Failed => 'failed',
        };
    }
}
