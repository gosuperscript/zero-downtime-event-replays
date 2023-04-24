<?php

namespace Gosuperscript\ZeroDowntimeEventReplays\Tests;

use Gosuperscript\ZeroDowntimeEventReplays\ZeroDowntimeEventReplaysServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Gosuperscript\\ZeroDowntimeEventReplays\\Database\\Factories\\'.class_basename($modelName).'Factory'
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
        //        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_zero-downtime-event-replays_table.php.stub';
        $migration->up();
        */
    }

    protected function setUpDatabase()
    {
        \DB::statement("DROP SCHEMA public CASCADE;");
        \DB::statement("CREATE SCHEMA public;");

        Schema::dropIfExists('comments');
        Schema::dropIfExists('posts');
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignUuid('post_id')->constrained();
            ;
            $table->string('content');
            $table->timestamps();
        });
    }
}
