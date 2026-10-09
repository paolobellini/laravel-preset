<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Enums\Group;

it('labels every group', function (Group $group, string $label) {
    expect($group->label())->toBe($label);
})->with([
    [Group::Configs, 'Lint / format / static-analysis configs + dependencies'],
    [Group::Ai, 'The .ai conventions and guidelines'],
    [Group::Scripts, 'Composer quality scripts'],
    [Group::Github, 'GitHub Actions workflows + dependency updates'],
    [Group::Lefthook, 'Lefthook pre-commit hooks (requires the lefthook binary)'],
    [Group::Skills, 'Agent skills for queues, transactions, scheduling, … (requires npx)'],
    [Group::Codegraph, 'CodeGraph index for this project (requires the codegraph binary)'],
]);

it('selects only the core groups by default', function () {
    expect(Group::defaults())->toBe([Group::Configs, Group::Ai, Group::Scripts, Group::Github]);
});

it('titles every group', function (Group $group, string $title) {
    expect($group->title())->toBe($title);
})->with([
    [Group::Configs, 'Tooling configs'],
    [Group::Ai, 'AI guidelines'],
    [Group::Scripts, 'Composer scripts and dependencies'],
    [Group::Github, 'GitHub Actions'],
    [Group::Lefthook, 'Lefthook'],
    [Group::Skills, 'Agent skills'],
    [Group::Codegraph, 'CodeGraph'],
]);
