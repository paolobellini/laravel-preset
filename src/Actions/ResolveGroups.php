<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use PaoloBellini\LaravelPreset\Enums\Group;

use function Laravel\Prompts\multiselect;

final readonly class ResolveGroups {
    /**
     * @param  array<int, Group>  $flagged
     * @return array<int, Group>
     */
    public function handle(array $flagged, bool $interactive): array {
        if ($flagged !== []) {
            return $flagged;
        }

        if (! $interactive) {
            return Group::defaults();
        }

        $options = [];

        foreach (Group::cases() as $group) {
            $options[$group->value] = $group->label();
        }

        /** @var array<int, string> $selected */
        $selected = multiselect(
            label: 'Which parts of the preset should be installed?',
            options: $options,
            default: array_column(Group::defaults(), 'value'),
            required: true,
        );

        return array_map(Group::from(...), $selected);
    }
}
