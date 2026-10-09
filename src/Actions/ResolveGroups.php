<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use PaoloBellini\LaravelPreset\Enums\Group;

use function Laravel\Prompts\confirm;
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

        $groups = [Group::Packages];

        $wantsDevTooling = confirm(
            label: 'Install the development tooling?',
            hint: 'Pint, PHPStan, Rector, Psalm and Pest configs, composer scripts and their dev dependencies.',
        );

        if ($wantsDevTooling) {
            $groups[] = Group::Configs;
            $groups[] = Group::Scripts;
        }

        $ai = $this->choose('Which AI tooling should be installed?', [Group::Ai, Group::Skills, Group::Codegraph]);
        $automation = $this->choose('Which automation should be installed?', [Group::Github, Group::Lefthook]);

        return [...$groups, ...$ai, ...$automation];
    }

    /**
     * @param  array<int, Group>  $candidates
     * @return array<int, Group>
     */
    private function choose(string $label, array $candidates): array {
        $options = [];
        $default = [];

        foreach ($candidates as $group) {
            $options[$group->value] = $group->label();

            if ($group->isDefault()) {
                $default[] = $group->value;
            }
        }

        /** @var array<int, string> $selected */
        $selected = multiselect(label: $label, options: $options, default: $default);

        return array_map(Group::from(...), $selected);
    }
}
