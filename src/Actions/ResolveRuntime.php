<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use PaoloBellini\LaravelPreset\Data\Environment;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Runtime;
use PaoloBellini\LaravelPreset\Exceptions\InvalidRuntime;

use function Laravel\Prompts\select;

final readonly class ResolveRuntime {
    public function __construct(private DetectEnvironment $detectEnvironment) {}

    public function handle(string $basePath, ?string $option, bool $interactive): ResolvedRuntime {
        $environment = $this->detectEnvironment->handle($basePath);

        return new ResolvedRuntime(
            runtime: $this->choose($environment, $option, $interactive),
            insideContainer: $environment->insideContainer,
        );
    }

    private function choose(Environment $environment, ?string $option, bool $interactive): Runtime {
        if ($option !== null) {
            $runtime = Runtime::tryFrom($option) ?? throw InvalidRuntime::unknown($option);

            if ($runtime === Runtime::Sail && ! $environment->sailConfigured) {
                throw InvalidRuntime::sailNotConfigured();
            }

            return $runtime;
        }

        $suggested = Runtime::suggestedFor($environment);
        $canChoose = $environment->sailConfigured && ! $environment->insideContainer;

        if (! $canChoose || ! $interactive) {
            return $suggested;
        }

        return Runtime::from((string) select(
            label: 'Where do this project\'s commands run?',
            options: [
                Runtime::Sail->value => 'Laravel Sail — in the containers',
                Runtime::Local->value => 'Locally — PHP and Node installed on this machine',
            ],
            default: $suggested->value,
        ));
    }
}
