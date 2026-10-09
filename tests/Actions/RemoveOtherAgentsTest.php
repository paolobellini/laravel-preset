<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\RemoveOtherAgents;
use PaoloBellini\LaravelPreset\Enums\Outcome;

it('removes the scaffolding of the other agents and reports only what it removed', function () {
    mkdir($this->appBase.'/.cursor');
    mkdir($this->appBase.'/.github');
    file_put_contents($this->appBase.'/.github/copilot-instructions.md', '');
    mkdir($this->appBase.'/.claude');

    expect(app(RemoveOtherAgents::class)->handle())->toBe([
        '.cursor' => Outcome::Removed,
        '.github/copilot-instructions.md' => Outcome::Removed,
    ])
        ->and($this->appBase.'/.cursor')->not->toBeDirectory()
        ->and($this->appBase.'/.claude')->toBeDirectory();
});

it('reports nothing on a clean project', function () {
    expect(app(RemoveOtherAgents::class)->handle())->toBe([]);
});
