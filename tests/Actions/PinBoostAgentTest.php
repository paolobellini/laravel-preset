<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\PinBoostAgent;
use PaoloBellini\LaravelPreset\Enums\Outcome;

it('creates boost.json pinned to claude code', function () {
    expect(app(PinBoostAgent::class)->handle())->toBe(Outcome::Created)
        ->and(json_decode(file_get_contents($this->appBase.'/boost.json'), true))->toBe(['agents' => ['claude_code']]);
});

it('narrows an existing boost.json and keeps its other keys', function () {
    file_put_contents($this->appBase.'/boost.json', json_encode(['agents' => ['cursor', 'claude_code'], 'guidelines' => true]));

    expect(app(PinBoostAgent::class)->handle())->toBe(Outcome::Patched)
        ->and(json_decode(file_get_contents($this->appBase.'/boost.json'), true))
        ->toBe(['agents' => ['claude_code'], 'guidelines' => true]);
});

it('leaves a boost.json that is already pinned', function () {
    file_put_contents($this->appBase.'/boost.json', '{"agents":["claude_code"]}');

    expect(app(PinBoostAgent::class)->handle())->toBe(Outcome::Skipped)
        ->and(file_get_contents($this->appBase.'/boost.json'))->toBe('{"agents":["claude_code"]}');
});
