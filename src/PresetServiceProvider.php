<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset;

use Illuminate\Support\ServiceProvider;
use PaoloBellini\LaravelPreset\Console\InstallCommand;

final class PresetServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
