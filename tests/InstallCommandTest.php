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
        ->and($this->appBase.'/.ai/guidelines/personal/php.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/query-builder.md')->toBeFile()
        ->and($this->appBase.'/.ai/guidelines/personal/typescript.md')->toBeFile()
        ->and($this->appBase.'/.ai/mcp/mcp.json')->toBeFile()
        ->and($this->appBase.'/.claude')->not->toBeDirectory()
        ->and($this->appBase.'/.junie')->not->toBeDirectory()
        ->and($this->appBase.'/.factory')->not->toBeDirectory()
        ->and($this->appBase.'/CLAUDE.md')->not->toBeFile();
});

it('copies the split github caller workflows', function () {
    Artisan::call('preset:install', ['--github' => true, '--no-interaction' => true]);

    expect($this->appBase.'/.github/workflows/analyse.yml')->toBeFile()
        ->and($this->appBase.'/.github/workflows/tests.yml')->toBeFile()
        ->and($this->appBase.'/.github/workflows/security.yml')->toBeFile()
        ->and($this->appBase.'/.github/workflows/ci.yml')->not->toBeFile();

    expect(file_get_contents($this->appBase.'/.github/workflows/analyse.yml'))
        ->toContain('paolobellini/bellini.one/.github/workflows/laravel-lint.yml@v1.0');
    expect(file_get_contents($this->appBase.'/.github/workflows/tests.yml'))
        ->toContain('paolobellini/bellini.one/.github/workflows/laravel-test.yml@v1.0');
    expect(file_get_contents($this->appBase.'/.github/workflows/security.yml'))
        ->toContain('branches: [staging]')
        ->toContain('paolobellini/bellini.one/actions/general/security@v1.0');
});

it('removes superseded starter-kit workflows', function () {
    $dir = $this->appBase.'/.github/workflows';
    mkdir($dir, 0777, true);
    file_put_contents($dir.'/lint.yml', 'name: starter-lint');
    file_put_contents($dir.'/tests.yml', 'name: starter-tests');

    Artisan::call('preset:install', ['--github' => true, '--no-interaction' => true]);

    expect($dir.'/lint.yml')->not->toBeFile();
    // starter tests.yml replaced by ours (references the reusable workflow)
    expect(file_get_contents($dir.'/tests.yml'))
        ->toContain('paolobellini/bellini.one/.github/workflows/laravel-test.yml@v1.0');
});

it('merges composer scripts and allows the pest plugin without npm scripts', function () {
    seed($this->appBase);
    Process::fake();

    Artisan::call('preset:install', ['--scripts' => true, '--no-interaction' => true]);

    $composer = json_decode(file_get_contents($this->appBase.'/composer.json'), true);

    expect($composer['scripts'])->toHaveKeys(['ci', 'pre-commit', 'pre-push', 'test:mutate', 'post-autoload-dump'])
        // entry points compose groups, they never re-list a leaf script
        ->and($composer['scripts']['ci:php'])->toBe(['@analyse', '@tests'])
        ->and($composer['scripts']['analyse'])->toBe(['@analyse:static', '@taint'])
        // pre-push owns what CI deliberately skips, and duplicates none of it
        ->and($composer['scripts']['pre-push'])->toBe(['@rector:test:dry', '@test:mutate'])
        ->and($composer['scripts']['tests'])->not->toContain('@test:mutate')
        ->and($composer['scripts']['analyse:static'])->not->toContain('@rector:test:dry')
        // app-booting scripts clear a cached config first, without inheriting extra args
        ->and($composer['scripts']['test:coverage'][0])->toBe('@php artisan config:clear --ansi @no_additional_args')
        ->and($composer['scripts']['test:mutate'][0])->toBe('Composer\\Config::disableProcessTimeout')
        // static-analysis scripts do not boot the app, so no clear
        ->and($composer['scripts']['pint'])->toBe('pint --parallel')
        ->and($composer['scripts']['taint'])->toBe('psalm --taint-analysis --no-cache')
        // ide-helper is no longer part of the preset
        ->and($composer['scripts'])->not->toHaveKey('ide-helper')
        ->and($composer['config']['allow-plugins'])->toHaveKey('pestphp/pest-plugin')
        // constraints are resolved by composer require, never written by the preset
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
            // dropped from the preset
            && ! str_contains($process->command, 'barryvdh/laravel-ide-helper')
            // no explicit versions, ever
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
