<?php

declare(strict_types=1);

use PaoloBellini\LaravelPreset\Actions\ResolveGroups;
use PaoloBellini\LaravelPreset\Enums\Group;

it('keeps the flagged groups without asking', function () {
    expect((new ResolveGroups())->handle([Group::Lefthook, Group::Skills], true))
        ->toBe([Group::Lefthook, Group::Skills]);
});

it('falls back to the default groups when not interactive', function () {
    expect((new ResolveGroups())->handle([], false))->toBe(Group::defaults());
});
