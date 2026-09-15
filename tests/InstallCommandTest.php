<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

function seed(string $base): void
{
    file_put_contents($base.'/composer.json', json_encode([
        'name' => 'acme/app',
        'require' => ['php' => '^8.3'],
        'require-dev' => ['phpunit/phpunit' => '^11.0'],
        'scripts' => ['post-autoload-dump' => ['@php artisan package:discover']],
    ], JSON_PRETTY_PRINT));
}

it('copies only the three tooling configs', function () {
    Artisan::call('preset:install', ['--configs' => true, '--no-interaction' => true]);

    expect($this->appBase.'/pint.json')->toBeFile()
        ->and($this->appBase.'/phpstan.neon')->toBeFile()
        ->and($this->appBase.'/rector.php')->toBeFile()
        ->and($this->appBase.'/rector-tests.php')->toBeFile()
        ->and($this->appBase.'/config/essentials.php')->toBeFile()
        ->and($this->appBase.'/psalm.xml')->toBeFile()
        ->and($this->appBase.'/lefthook.yml')->not->toBeFile()
        ->and($this->appBase.'/eslint.config.js')->not->toBeFile()
        ->and($this->appBase.'/tsconfig.json')->not->toBeFile()
        ->and($this->appBase.'/.prettierrc')->not->toBeFile();

    expect(file_get_contents($this->appBase.'/config/essentials.php'))
        ->toContain('Unguard::class => true');
});

it('creates tests/Pest.php with tia enabled locally when the project has none', function () {
    Artisan::call('preset:install', ['--configs' => true, '--no-interaction' => true]);

    expect($this->appBase.'/tests/Pest.php')->toBeFile();

    expect(file_get_contents($this->appBase.'/tests/Pest.php'))
        ->toContain('pest()->tia()->locally();')
        ->toContain('pest()->extend(TestCase::class)')
        ->toContain('->use(RefreshDatabase::class)')
        ->toContain("->in('Feature', 'Unit');");
});

it('appends tia to an existing tests/Pest.php without touching its setup', function () {
    mkdir($this->appBase.'/tests', 0777, true);
    file_put_contents($this->appBase.'/tests/Pest.php', <<<'PHP'
        <?php

        uses(Tests\TestCase::class)->in('Feature');

        function somethingCustom(): void {}

        PHP);

    Artisan::call('preset:install', ['--configs' => true, '--no-interaction' => true]);

    expect(file_get_contents($this->appBase.'/tests/Pest.php'))
        ->toContain('function somethingCustom(): void {}')
        ->toContain("uses(Tests\TestCase::class)->in('Feature');")
        ->toContain('pest()->tia()->locally();');
});

it('leaves tests/Pest.php alone when it already configures tia', function () {
    mkdir($this->appBase.'/tests', 0777, true);
    $original = "<?php\n\npest()->tia()->always();\n";
    file_put_contents($this->appBase.'/tests/Pest.php', $original);

    Artisan::call('preset:install', ['--configs' => true, '--no-interaction' => true]);

    expect(file_get_contents($this->appBase.'/tests/Pest.php'))->toBe($original);
});

it('installs lefthook only when asked for', function () {
    Artisan::call('preset:install', ['--no-interaction' => true]);

    expect($this->appBase.'/lefthook.yml')->not->toBeFile()
        ->and($this->appBase.'/pint.json')->toBeFile();
});

it('keeps the sail prefix in lefthook.yml when sail is configured', function () {
    mkdir($this->appBase.'/vendor/bin', 0777, true);
    file_put_contents($this->appBase.'/vendor/bin/sail', "#!/bin/sh\n");
    file_put_contents($this->appBase.'/compose.yaml', "services: {}\n");

    Artisan::call('preset:install', ['--lefthook' => true, '--no-interaction' => true]);

    expect(file_get_contents($this->appBase.'/lefthook.yml'))
        ->toContain('vendor/bin/sail composer pint -- {staged_files}')
        ->toContain('vendor/bin/sail composer stan -- {staged_files}')
        ->toContain('vendor/bin/sail composer test:coverage')
        ->toContain('vendor/bin/sail composer test:mutate');
});

it('strips the sail prefix from lefthook.yml when sail is not installed', function () {
    Artisan::call('preset:install', ['--lefthook' => true, '--no-interaction' => true]);

    $lefthook = file_get_contents($this->appBase.'/lefthook.yml');

    expect($lefthook)->not->toContain('vendor/bin/sail')
        ->and($lefthook)->toContain('run: composer pint -- {staged_files}')
        ->and($lefthook)->toContain('run: composer stan -- {staged_files}')
        ->and($lefthook)->toContain('run: composer test:mutate');
});

it('strips the sail prefix when sail is installed but not configured', function () {
    mkdir($this->appBase.'/vendor/bin', 0777, true);
    file_put_contents($this->appBase.'/vendor/bin/sail', "#!/bin/sh\n");
    // no compose.yaml / docker-compose.yml

    Artisan::call('preset:install', ['--lefthook' => true, '--no-interaction' => true]);

    expect(file_get_contents($this->appBase.'/lefthook.yml'))
        ->not->toContain('vendor/bin/sail')
        ->toContain('run: composer pint -- {staged_files}');
});

it('removes the scaffolding of every agent but claude code', function () {
    foreach (['.junie', '.cursor', '.factory', '.zed', '.gemini', '.claude'] as $dir) {
        mkdir($this->appBase.'/'.$dir, 0777, true);
        file_put_contents($this->appBase.'/'.$dir.'/config.json', '{}');
    }

    mkdir($this->appBase.'/.github', 0777, true);
    file_put_contents($this->appBase.'/.github/copilot-instructions.md', 'instructions');

    Artisan::call('preset:install', ['--ai' => true, '--no-interaction' => true]);

    expect($this->appBase.'/.junie')->not->toBeDirectory()
        ->and($this->appBase.'/.cursor')->not->toBeDirectory()
        ->and($this->appBase.'/.factory')->not->toBeDirectory()
        ->and($this->appBase.'/.zed')->not->toBeDirectory()
        ->and($this->appBase.'/.gemini')->not->toBeDirectory()
        ->and($this->appBase.'/.github/copilot-instructions.md')->not->toBeFile()
        ->and($this->appBase.'/.claude')->toBeDirectory();
});

it('leaves the editor directories alone', function () {
    foreach (['.idea', '.vscode'] as $dir) {
        mkdir($this->appBase.'/'.$dir, 0777, true);
        file_put_contents($this->appBase.'/'.$dir.'/workspace.xml', 'mine');
    }

    Artisan::call('preset:install', ['--ai' => true, '--no-interaction' => true]);

    expect($this->appBase.'/.idea')->toBeDirectory()
        ->and($this->appBase.'/.vscode')->toBeDirectory();
});

it('pins boost to claude code without dropping the rest of boost.json', function () {
    file_put_contents($this->appBase.'/boost.json', json_encode([
        'agents' => ['claude_code', 'junie', 'cursor'],
        'guidelines' => ['laravel'],
    ]));

    Artisan::call('preset:install', ['--ai' => true, '--no-interaction' => true]);

    $boost = json_decode(file_get_contents($this->appBase.'/boost.json'), true);

    expect($boost['agents'])->toBe(['claude_code'])
        ->and($boost['guidelines'])->toBe(['laravel']);
});

it('creates boost.json when the project has none', function () {
    Artisan::call('preset:install', ['--ai' => true, '--no-interaction' => true]);

    expect(json_decode(file_get_contents($this->appBase.'/boost.json'), true)['agents'])->toBe(['claude_code']);
});

it('copies only the .ai conventions, nothing else', function () {
    Artisan::call('preset:install', ['--ai' => true, '--no-interaction' => true]);

    expect($this->appBase.'/.ai/guidelines/personal/controllers.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/actions.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/caching.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/enums.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/frontend.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/policies.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/translations.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/exceptions.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/resources.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/traits.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/form-requests.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/pest-agent.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/php.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/query-builder.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/typescript.md')->toBeFile()
        ->and($this->appBase.'/.ai/mcp/mcp.json')->toBeFile()
        ->and($this->appBase.'/.claude')->not->toBeDirectory()
        ->and($this->appBase.'/CLAUDE.md')->not->toBeFile();
});

it('copies the github workflows', function () {
    Artisan::call('preset:install', ['--github' => true, '--no-interaction' => true]);

    expect($this->appBase.'/.github/dependabot.yml')->toBeFile()
        ->and($this->appBase.'/.github/workflows/analyse.yml')->toBeFile()
        ->and($this->appBase.'/.github/workflows/tests.yml')->toBeFile()
        ->and($this->appBase.'/.github/workflows/security.yml')->toBeFile()
        ->and($this->appBase.'/.github/workflows/ci.yml')->not->toBeFile();

    expect(file_get_contents($this->appBase.'/.github/workflows/analyse.yml'))
        ->toContain('paolobellini/bellini.one/actions/laravel/setup-app@v1.1')
        ->toContain('install-node: ${{ steps.changes.outputs.frontend }}')
        ->toContain('composer analyse')
        ->toContain('composer ci:node')
        ->toContain('dorny/paths-filter@v4.0.3')
        ->toContain("steps.changes.outputs.frontend == 'true'");
    expect(file_get_contents($this->appBase.'/.github/workflows/tests.yml'))
        ->toContain('paolobellini/bellini.one/actions/laravel/setup-app@v1.1')
        ->toContain("image: \${{ vars.DB_IMAGE || 'mysql:8.0' }}")
        ->toContain('composer tests');
    expect(file_get_contents($this->appBase.'/.github/dependabot.yml'))
        ->toContain('package-ecosystem: composer')
        ->toContain('package-ecosystem: npm')
        ->toContain('package-ecosystem: github-actions')
        ->toContain('prefix: chore(deps)')
        ->toContain('semver-major-days: 14');

    expect(file_get_contents($this->appBase.'/.github/workflows/security.yml'))
        ->toContain('branches: [main, staging, dev]')
        ->toContain('aquasecurity/trivy-action@v0.36.0')
        ->toContain('trivy-config: .github/trivy.yaml')
        ->toContain("exit-code: '1'");

    expect(file_get_contents($this->appBase.'/.github/trivy.yaml'))
        ->toContain('vex:')
        ->toContain('.vex/openvex.json');

    $vex = json_decode(file_get_contents($this->appBase.'/.vex/openvex.json'), true);

    expect($vex['@context'])->toBe('https://openvex.dev/ns/v0.2.0')
        ->and($vex['statements'])->toBe([])
        ->and($vex['@id'])->toStartWith('urn:vex:')
        ->and($vex['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/');
});

it('replaces the tests workflow with the sharded variant when asked for', function () {
    Artisan::call('preset:install', ['--github' => true, '--sharded' => true, '--no-interaction' => true]);

    expect(file_get_contents($this->appBase.'/.github/workflows/tests.yml'))
        ->toContain('matrix:')
        ->toContain('shard: [1, 2, 3, 4]')
        ->toContain('composer test -- --shard=${{ matrix.shard }}/${{ strategy.job-total }}')
        ->toContain('composer test:type-coverage');

    expect(file_get_contents($this->appBase.'/.github/workflows/update-shards.yml'))
        ->toContain("cron: '17 4 * * 1'")
        ->toContain('contents: write')
        ->toContain('composer update-shards')
        ->toContain('git push');

    expect(file_get_contents($this->appBase.'/.github/workflows/tests.yml'))
        ->not->toContain('composer tests');
});

it('shards an already installed tests workflow without --force', function () {
    Artisan::call('preset:install', ['--github' => true, '--no-interaction' => true]);

    expect(file_get_contents($this->appBase.'/.github/workflows/tests.yml'))->not->toContain('matrix:');

    Artisan::call('preset:install', ['--github' => true, '--sharded' => true, '--no-interaction' => true]);

    expect(file_get_contents($this->appBase.'/.github/workflows/tests.yml'))->toContain('shard: [1, 2, 3, 4]');
});

it('reverts to the single-job tests workflow with --force', function () {
    Artisan::call('preset:install', ['--github' => true, '--sharded' => true, '--no-interaction' => true]);

    Artisan::call('preset:install', ['--github' => true, '--force' => true, '--no-interaction' => true]);

    expect(file_get_contents($this->appBase.'/.github/workflows/tests.yml'))
        ->toContain('composer tests')
        ->not->toContain('matrix:');
});

it('keeps the single-job tests workflow without --sharded', function () {
    Artisan::call('preset:install', ['--github' => true, '--no-interaction' => true]);

    expect(file_get_contents($this->appBase.'/.github/workflows/tests.yml'))
        ->toContain('composer tests')
        ->not->toContain('matrix:');
});

it('does not install the update-shards workflow without --sharded', function () {
    Artisan::call('preset:install', ['--github' => true, '--no-interaction' => true]);

    expect($this->appBase.'/.github/workflows/update-shards.yml')->not->toBeFile();
});

it('swaps dependabot for renovate when asked for', function () {
    Artisan::call('preset:install', ['--github' => true, '--renovate' => true, '--no-interaction' => true]);

    expect($this->appBase.'/.github/dependabot.yml')->not->toBeFile()
        ->and($this->appBase.'/.github/renovate.json')->toBeFile();

    $renovate = json_decode(file_get_contents($this->appBase.'/.github/renovate.json'), true);

    expect($renovate['extends'])->toBe(['config:recommended'])
        ->and($renovate['minimumReleaseAge'])->toBe('7 days')
        ->and($renovate['vulnerabilityAlerts']['minimumReleaseAge'])->toBeNull()
        ->and($renovate['lockFileMaintenance']['enabled'])->toBeTrue()
        ->and($renovate['schedule'])->toBe(['* 0-6 * * 1'])
        ->and($renovate['lockFileMaintenance']['schedule'])->toBe(['* * 1-7 * 1'])
        ->and($renovate)->not->toHaveKey('dependencyDashboard');

    expect($this->appBase.'/.github/workflows/renovate.yml')->not->toBeFile();
});

it('installs the self-hosted renovate workflow with --renovate-selfhosted', function () {
    Artisan::call('preset:install', ['--github' => true, '--renovate-selfhosted' => true, '--no-interaction' => true]);

    expect($this->appBase.'/.github/renovate.json')->toBeFile()
        ->and($this->appBase.'/.github/dependabot.yml')->not->toBeFile();

    expect(file_get_contents($this->appBase.'/.github/workflows/renovate.yml'))
        ->toContain('renovatebot/github-action@v46.3.1')
        ->toContain('secrets.RENOVATE_TOKEN')
        ->toContain('RENOVATE_REPOSITORIES: ${{ github.repository }}')
        ->toContain("cron: '0 0-6 * * 1'");
});

it('keeps dependabot without --renovate', function () {
    Artisan::call('preset:install', ['--github' => true, '--no-interaction' => true]);

    expect($this->appBase.'/.github/dependabot.yml')->toBeFile()
        ->and($this->appBase.'/.github/renovate.json')->not->toBeFile();
});

it('combines --sharded and --renovate', function () {
    Artisan::call('preset:install', ['--github' => true, '--sharded' => true, '--renovate' => true, '--no-interaction' => true]);

    expect($this->appBase.'/.github/renovate.json')->toBeFile()
        ->and($this->appBase.'/.github/workflows/update-shards.yml')->toBeFile()
        ->and($this->appBase.'/.github/dependabot.yml')->not->toBeFile();
});

it('derives the vex document identifier from the project name', function () {
    file_put_contents($this->appBase.'/composer.json', json_encode(['name' => 'paolobellini/bookminer']));

    Artisan::call('preset:install', ['--github' => true, '--no-interaction' => true]);

    $vex = json_decode(file_get_contents($this->appBase.'/.vex/openvex.json'), true);

    expect($vex['@id'])->toBe('urn:vex:paolobellini:bookminer');
});

it('leaves an existing vex document untouched', function () {
    mkdir($this->appBase.'/.vex', 0777, true);
    file_put_contents($this->appBase.'/.vex/openvex.json', '{"statements":["mine"]}');

    Artisan::call('preset:install', ['--github' => true, '--no-interaction' => true]);

    expect(json_decode(file_get_contents($this->appBase.'/.vex/openvex.json'), true)['statements'])->toBe(['mine']);
});

it('removes superseded starter-kit workflows', function () {
    $dir = $this->appBase.'/.github/workflows';
    mkdir($dir, 0777, true);
    file_put_contents($dir.'/lint.yml', 'name: starter-lint');
    file_put_contents($dir.'/tests.yml', 'name: starter-tests');

    Artisan::call('preset:install', ['--github' => true, '--no-interaction' => true]);

    expect($dir.'/lint.yml')->not->toBeFile();
    expect(file_get_contents($dir.'/tests.yml'))
        ->toContain('composer tests');
});

it('merges composer scripts and allows the pest plugin without npm scripts', function () {
    seed($this->appBase);
    Process::fake();

    Artisan::call('preset:install', ['--scripts' => true, '--no-interaction' => true]);

    $composer = json_decode(file_get_contents($this->appBase.'/composer.json'), true);

    expect($composer['scripts'])->toHaveKeys(['ci', 'pre-commit', 'pre-push', 'test:mutate', 'post-autoload-dump'])
        ->and($composer['scripts']['ci:php'])->toBe(['@analyse', '@tests'])
        ->and($composer['scripts']['analyse'])->toBe(['@analyse:static', '@taint'])
        ->and($composer['scripts']['pre-push'])->toBe(['@rector:test:dry', '@test:mutate'])
        ->and($composer['scripts']['tests'])->not->toContain('@test:mutate')
        ->and($composer['scripts']['analyse:static'])->not->toContain('@rector:test:dry')
        ->and($composer['scripts']['test:coverage'][0])->toBe('@php artisan config:clear --ansi @no_additional_args')
        ->and($composer['scripts']['test:mutate'][0])->toBe('Composer\\Config::disableProcessTimeout')
        ->and($composer['scripts']['pint'])->toBe('pint --parallel')
        ->and($composer['scripts']['taint'])->toBe('psalm --taint-analysis --no-cache')
        ->and($composer['scripts'])->not->toHaveKey('ide-helper')
        ->and($composer['config']['allow-plugins'])->toHaveKey('pestphp/pest-plugin')
        ->and($composer['require'])->not->toHaveKey('nunomaduro/essentials')
        ->and($composer['require'])->not->toHaveKey('spatie/laravel-data')
        ->and($composer['require-dev'])->not->toHaveKey('rector/rector');

    expect($this->appBase.'/package.json')->not->toBeFile();
});

it('requires the preset dependencies unconstrained so composer resolves the latest', function () {
    seed($this->appBase);
    Process::fake();

    Artisan::call('preset:install', ['--scripts' => true, '--no-interaction' => true]);

    Process::assertRan(fn ($process) => $process->command === 'composer require --no-interaction nunomaduro/essentials spatie/laravel-data spatie/laravel-query-builder thecodingmachine/safe');

    Process::assertRan(function ($process) {
        return str_starts_with($process->command, 'composer require --dev --no-interaction ')
            && str_contains($process->command, 'pestphp/pest ')
            && str_contains($process->command, 'pestphp/pest-plugin-rector')
            && str_contains($process->command, 'pestphp/pest-plugin-phpstan')
            && str_contains($process->command, 'pestphp/pest-plugin-evals')
            && str_contains($process->command, 'pestphp/pest-plugin-agent')
            && str_contains($process->command, 'pestphp/pest-plugin-faker')
            && str_contains($process->command, 'pestphp/pest-plugin-mutate')
            && str_contains($process->command, 'rector/rector')
            && str_contains($process->command, 'thecodingmachine/phpstan-safe-rule')
            && str_contains($process->command, 'spatie/laravel-typescript-transformer')
            && str_contains($process->command, 'vimeo/psalm')
            && ! str_contains($process->command, 'barryvdh/laravel-ide-helper')
            && ! str_contains($process->command, ':^');
    });
});

it('skips packages the project already requires', function () {
    seed($this->appBase);
    $composer = json_decode(file_get_contents($this->appBase.'/composer.json'), true);
    $composer['require-dev']['rector/rector'] = '^1.0';
    file_put_contents($this->appBase.'/composer.json', json_encode($composer, JSON_PRETTY_PRINT));
    Process::fake();

    Artisan::call('preset:install', ['--scripts' => true, '--no-interaction' => true]);

    Process::assertNotRan(fn ($process) => str_contains($process->command, 'rector/rector'));

    expect(json_decode(file_get_contents($this->appBase.'/composer.json'), true)['require-dev']['rector/rector'])
        ->toBe('^1.0');
});

it('re-requires already present packages with --force', function () {
    seed($this->appBase);
    $composer = json_decode(file_get_contents($this->appBase.'/composer.json'), true);
    $composer['require-dev']['rector/rector'] = '^1.0';
    file_put_contents($this->appBase.'/composer.json', json_encode($composer, JSON_PRETTY_PRINT));
    Process::fake();

    Artisan::call('preset:install', ['--scripts' => true, '--force' => true, '--no-interaction' => true]);

    Process::assertRan(fn ($process) => str_contains($process->command, 'rector/rector'));
});

it('uses sail composer only when sail is installed and configured', function () {
    seed($this->appBase);
    mkdir($this->appBase.'/vendor/bin', 0777, true);
    file_put_contents($this->appBase.'/vendor/bin/sail', "#!/bin/sh\n");
    file_put_contents($this->appBase.'/compose.yaml', "services: {}\n");
    Process::fake();

    Artisan::call('preset:install', ['--scripts' => true, '--no-interaction' => true]);

    Process::assertRan(fn ($process) => str_starts_with($process->command, './vendor/bin/sail composer require '));
});

it('falls back to plain composer when sail is installed but not configured', function () {
    seed($this->appBase);
    mkdir($this->appBase.'/vendor/bin', 0777, true);
    file_put_contents($this->appBase.'/vendor/bin/sail', "#!/bin/sh\n");
    // no compose.yaml / docker-compose.yml
    Process::fake();

    Artisan::call('preset:install', ['--scripts' => true, '--no-interaction' => true]);

    Process::assertRan(fn ($process) => str_starts_with($process->command, 'composer require '));
});

it('writes the constraints without installing with --no-install', function () {
    seed($this->appBase);
    Process::fake();

    Artisan::call('preset:install', ['--scripts' => true, '--no-install' => true, '--no-interaction' => true]);

    Process::assertRan(fn ($process) => str_contains($process->command, 'composer require --no-interaction --no-update '));
});

it('skips existing files unless forced', function () {
    file_put_contents($this->appBase.'/pint.json', '{"mine":true}');

    Artisan::call('preset:install', ['--configs' => true, '--no-interaction' => true]);
    expect(json_decode(file_get_contents($this->appBase.'/pint.json'), true))->toHaveKey('mine');

    Artisan::call('preset:install', ['--configs' => true, '--force' => true, '--no-interaction' => true]);
    expect(json_decode(file_get_contents($this->appBase.'/pint.json'), true))->toHaveKey('preset', 'laravel');
});
