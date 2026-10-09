<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Enums;

enum Group: string {
    case Configs = 'configs';
    case Ai = 'ai';
    case Scripts = 'scripts';
    case Github = 'github';
    case Lefthook = 'lefthook';
    case Skills = 'skills';
    case Codegraph = 'codegraph';

    /**
     * @return array<int, self>
     */
    public static function defaults(): array {
        return array_values(array_filter(self::cases(), fn (self $group): bool => $group->isDefault()));
    }

    public function title(): string {
        return match ($this) {
            self::Configs => 'Tooling configs',
            self::Ai => 'AI guidelines',
            self::Scripts => 'Composer scripts and dependencies',
            self::Github => 'GitHub Actions',
            self::Lefthook => 'Lefthook',
            self::Skills => 'Agent skills',
            self::Codegraph => 'CodeGraph',
        };
    }

    public function label(): string {
        return match ($this) {
            self::Configs => 'Lint / format / static-analysis configs + dependencies',
            self::Ai => 'The .ai conventions and guidelines',
            self::Scripts => 'Composer quality scripts',
            self::Github => 'GitHub Actions workflows + dependency updates',
            self::Lefthook => 'Lefthook pre-commit hooks (requires the lefthook binary)',
            self::Skills => 'Agent skills for queues, transactions, scheduling, … (requires npx)',
            self::Codegraph => 'CodeGraph index for this project (requires the codegraph binary)',
        };
    }

    public function isDefault(): bool {
        return match ($this) {
            self::Configs, self::Ai, self::Scripts, self::Github => true,
            self::Lefthook, self::Skills, self::Codegraph => false,
        };
    }
}
