<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

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
        ->and($this->appBase.'/config/essentials.php')->toBeFile()
        ->and($this->appBase.'/eslint.config.js')->not->toBeFile()
        ->and($this->appBase.'/tsconfig.json')->not->toBeFile()
        ->and($this->appBase.'/.prettierrc')->not->toBeFile();

    expect(file_get_contents($this->appBase.'/config/essentials.php'))
        ->toContain('Unguard::class => true');
});

it('copies only the .ai conventions, nothing else', function () {
    Artisan::call('preset:install', ['--ai' => true, '--no-interaction' => true]);

    expect($this->appBase.'/.ai/guidelines/personal/controllers.md')->toBeFile()
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

it('merges composer scripts and dev deps without npm or duplicates', function () {
    seed($this->appBase);

    Artisan::call('preset:install', ['--scripts' => true, '--no-install' => true, '--no-interaction' => true]);

    $composer = json_decode(file_get_contents($this->appBase.'/composer.json'), true);

    expect($composer['scripts'])->toHaveKeys(['cleanup', 'ide-helper', 'php-checks', 'post-autoload-dump'])
        ->and($composer['scripts']['ide-helper'])->toContain('@php artisan ide-helper:models -RW')
        ->and($composer['require'])->toHaveKey('nunomaduro/essentials')
        ->and($composer['require-dev'])->toHaveKeys([
            'barryvdh/laravel-ide-helper',
            'fruitcake/laravel-debugbar',
            'laravel/pint',
            'rector/rector',
            'phpunit/phpunit',
        ])
        // these ship with the starter kit already — preset must not add them
        ->and($composer['require-dev'])->not->toHaveKey('nunomaduro/collision')
        ->and($composer['require-dev'])->not->toHaveKey('pestphp/pest-plugin-laravel');

    expect($this->appBase.'/package.json')->not->toBeFile();
});

it('skips existing files unless forced', function () {
    file_put_contents($this->appBase.'/pint.json', '{"mine":true}');

    Artisan::call('preset:install', ['--configs' => true, '--no-interaction' => true]);
    expect(json_decode(file_get_contents($this->appBase.'/pint.json'), true))->toHaveKey('mine');

    Artisan::call('preset:install', ['--configs' => true, '--force' => true, '--no-interaction' => true]);
    expect(json_decode(file_get_contents($this->appBase.'/pint.json'), true))->toHaveKey('preset', 'laravel');
});
