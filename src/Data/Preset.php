<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Data;

use PaoloBellini\LaravelPreset\Enums\Group;
use PaoloBellini\LaravelPreset\Enums\Package;
use PaoloBellini\LaravelPreset\Enums\Runtime;

final readonly class Preset {
    /**
     * @param  array<int, Package>  $packages
     * @param  array<int, Group>  $groups
     */
    public function __construct(
        public Runtime $runtime,
        public array $packages,
        public array $groups,
    ) {}
}
