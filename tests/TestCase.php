<?php

namespace Mannum\ZeroDowntimeEventReplays\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Mannum\ZeroDowntimeEventReplays\ZeroDowntimeEventReplaysServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Mannum\\ZeroDowntimeEventReplays\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ZeroDowntimeEventReplaysServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_zero-downtime-event-replays_table.php.stub';
        $migration->up();
        */
    }
}
