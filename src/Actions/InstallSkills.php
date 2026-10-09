<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Actions;

use Closure;
use PaoloBellini\LaravelPreset\Enums\Outcome;

final readonly class InstallSkills {
    /**
     * @var array<string, array<int, string>>
     */
    private const SKILLS = [
        'jpcaparas/superpowers-laravel' => [
            'laravel:queues-and-horizon',
            'laravel:http-client-resilience',
            'laravel:performance-select-columns',
        ],
        'mattpocock/skills' => [
            'wait-what',
            'teach',
        ],
    ];

    /**
     * @var array<int, string>
     */
    private const AGENTS = ['universal', 'claude-code'];

    public function __construct(private RunProcess $runProcess) {}

    /**
     * @param  Closure(string): void  $write
     * @return array<string, Outcome>
     */
    public function handle(Closure $write): array {
        $outcomes = [];

        foreach (self::SKILLS as $source => $skills) {
            $arguments = ['add', $source];

            foreach ($skills as $skill) {
                $arguments[] = '--skill';
                $arguments[] = $skill;
            }

            foreach (self::AGENTS as $agent) {
                $arguments[] = '--agent';
                $arguments[] = $agent;
            }

            $outcomes[$source] = $this->runProcess->handle('npx --yes skills@latest '.implode(' ', $arguments), $write)
                ? Outcome::Ran
                : Outcome::Failed;
        }

        return $outcomes;
    }
}
