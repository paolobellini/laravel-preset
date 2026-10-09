<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Console;

use Illuminate\Console\Command;
use PaoloBellini\LaravelPreset\Actions\BuildCommand;
use PaoloBellini\LaravelPreset\Actions\InstallAi;
use PaoloBellini\LaravelPreset\Actions\InstallCodegraph;
use PaoloBellini\LaravelPreset\Actions\InstallConfigs;
use PaoloBellini\LaravelPreset\Actions\InstallGithub;
use PaoloBellini\LaravelPreset\Actions\InstallLefthook;
use PaoloBellini\LaravelPreset\Actions\InstallScripts;
use PaoloBellini\LaravelPreset\Actions\InstallSkills;
use PaoloBellini\LaravelPreset\Actions\ResolveGroups;
use PaoloBellini\LaravelPreset\Actions\ResolveRuntime;
use PaoloBellini\LaravelPreset\Data\ResolvedRuntime;
use PaoloBellini\LaravelPreset\Enums\Group;
use PaoloBellini\LaravelPreset\Enums\Outcome;
use PaoloBellini\LaravelPreset\Exceptions\InvalidRuntime;

final class InstallCommand extends Command {
    protected $signature = 'preset:install
        {--configs : Install lint/format/static-analysis configs and their dependencies}
        {--ai : Install the .ai conventions and guidelines}
        {--scripts : Install composer quality scripts}
        {--github : Install GitHub Actions workflows and the dependency update config}
        {--lefthook : Install the lefthook pre-commit config (opt-in)}
        {--skills : Install the agent skills into .agents/skills (opt-in)}
        {--codegraph : Build the CodeGraph index for this project (opt-in)}
        {--sharded : Replace the tests workflow with the sharded matrix variant}
        {--renovate : Use renovate instead of dependabot for dependency updates}
        {--renovate-selfhosted : Also install the scheduled workflow that runs renovate without the GitHub App}
        {--runtime= : Where the project commands run: sail or local (detected when omitted)}
        {--force : Overwrite files that already exist}
        {--no-install : Write the new dependencies into composer.json without installing them}';

    protected $description = 'Scaffold the personal Laravel preset: tooling configs, conventions, scripts and CI.';

    private ResolvedRuntime $resolved;

    public function __construct(
        private readonly ResolveRuntime $resolveRuntime,
        private readonly ResolveGroups $resolveGroups,
        private readonly BuildCommand $buildCommand,
        private readonly InstallConfigs $installConfigs,
        private readonly InstallAi $installAi,
        private readonly InstallScripts $installScripts,
        private readonly InstallGithub $installGithub,
        private readonly InstallLefthook $installLefthook,
        private readonly InstallSkills $installSkills,
        private readonly InstallCodegraph $installCodegraph,
    ) {
        parent::__construct();
    }

    public function handle(): int {
        $option = $this->option('runtime');

        try {
            $this->resolved = $this->resolveRuntime->handle(
                $this->laravel->basePath(),
                is_string($option) ? $option : null,
                $this->input->isInteractive(),
            );
        } catch (InvalidRuntime $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Runtime', $this->resolved->runtime->label());

        $flagged = array_values(array_filter(
            Group::cases(),
            fn (Group $group): bool => (bool) $this->option($group->value),
        ));

        $resolvedGroups = $this->resolveGroups->handle($flagged, $this->input->isInteractive());

        foreach (Group::cases() as $group) {
            if (in_array($group, $resolvedGroups, true)) {
                $this->report($group->title(), $this->install($group));
            }
        }

        $this->newLine();
        $this->components->info('Preset installed.');
        $this->components->bulletList(array_values(array_filter([
            $this->option('no-install')
                ? "Run <fg=cyan>{$this->command('composer update')}</> to pull the new PHP dependencies."
                : null,
            in_array(Group::Scripts, $resolvedGroups, true)
                ? "Run <fg=cyan>{$this->command('composer ci')}</> to verify everything passes."
                : null,
            in_array(Group::Lefthook, $resolvedGroups, true)
                ? 'Run <fg=cyan>lefthook install</> to wire up the git hooks.'
                : null,
            in_array(Group::Skills, $resolvedGroups, true)
                ? 'Commit <fg=cyan>.agents/skills</> and <fg=cyan>skills-lock.json</>; <fg=cyan>npx skills update</> refreshes them.'
                : null,
            in_array(Group::Ai, $resolvedGroups, true)
                ? "Run <fg=cyan>{$this->command('php artisan boost:install')}</> — <fg=cyan>boost.json</> already pins it to Claude Code."
                : null,
        ])));

        return self::SUCCESS;
    }

    /**
     * @return array<string, Outcome>
     */
    private function install(Group $group): array {
        $force = (bool) $this->option('force');
        $interactive = $this->input->isInteractive();
        $write = fn (string $text) => $this->output->write($text);

        return match ($group) {
            Group::Configs => $this->installConfigs->handle($force),
            Group::Ai => $this->installAi->handle($force),
            Group::Scripts => $this->installScripts->handle(
                $this->resolved,
                $force,
                (bool) $this->option('no-install'),
                $write,
            ),
            Group::Github => $this->installGithub->handle(
                $force,
                (bool) $this->option('sharded'),
                (bool) $this->option('renovate'),
                (bool) $this->option('renovate-selfhosted'),
            ),
            Group::Lefthook => $this->installLefthook->handle($this->resolved->runtime, $force),
            Group::Skills => $this->installSkills->handle($write),
            Group::Codegraph => $this->installCodegraph->handle($this->resolved, $force, $interactive, $write),
        };
    }

    /**
     * @param  array<string, Outcome>  $outcomes
     */
    private function report(string $title, array $outcomes): void {
        $this->components->info($title);

        foreach ($outcomes as $subject => $outcome) {
            $this->components->twoColumnDetail($subject, $outcome->label());
        }
    }

    private function command(string $command): string {
        return $this->buildCommand->handle($this->resolved, $command);
    }
}
