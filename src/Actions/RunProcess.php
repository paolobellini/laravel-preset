<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Process;

final readonly class RunProcess {
    public function __construct(private Application $app) {}

    /**
     * @param  Closure(string): void  $write
     */
    public function handle(string $command, Closure $write): bool {
        $write(PHP_EOL."$ {$command}".PHP_EOL);

        return Process::path($this->app->basePath())
            ->forever()
            ->run($command, fn (string $type, string $output) => $write($output))
            ->successful();
    }
}
