<?php

declare(strict_types=1);

namespace PaoloBellini\LaravelPreset\Tests;

use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;
use PaoloBellini\LaravelPreset\PresetServiceProvider;

abstract class TestCase extends Orchestra
{
    protected string $appBase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appBase = sys_get_temp_dir().'/preset-test-'.uniqid();
        (new Filesystem())->ensureDirectoryExists($this->appBase);
        $this->app->setBasePath($this->appBase);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->appBase);

        parent::tearDown();
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [PresetServiceProvider::class];
    }
}
