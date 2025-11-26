<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Tests;

use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase as Orchestra;
use Outerweb\ImageLibrary\ImageLibraryServiceProvider;
use Outerweb\ImageLibrary\Tests\Fixtures\Providers\ImageLibraryServiceProvider as TestFixtureImageLibraryServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        Model::shouldBeStrict(true);

        foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
    }

    protected function getPackageProviders($app)
    {
        return [
            ImageLibraryServiceProvider::class,
            TestFixtureImageLibraryServiceProvider::class,
        ];
    }
}
