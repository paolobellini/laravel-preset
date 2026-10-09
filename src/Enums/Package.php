<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Enums;

enum Package: string {
    case Data = 'data';
    case QueryBuilder = 'query-builder';
    case TypescriptTransformer = 'typescript-transformer';

    public function label(): string {
        return match ($this) {
            self::Data => 'Laravel Data — typed data objects',
            self::QueryBuilder => 'Laravel Query Builder — filters, sorts and includes from the request',
            self::TypescriptTransformer => 'TypeScript Transformer — TypeScript types from PHP classes',
        };
    }

    public function composerName(): string {
        return match ($this) {
            self::Data => 'spatie/laravel-data',
            self::QueryBuilder => 'spatie/laravel-query-builder',
            self::TypescriptTransformer => 'spatie/laravel-typescript-transformer',
        };
    }

    public function needsInertia(): bool {
        return match ($this) {
            self::Data, self::QueryBuilder => false,
            self::TypescriptTransformer => true,
        };
    }
}
