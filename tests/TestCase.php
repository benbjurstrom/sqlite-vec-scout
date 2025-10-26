<?php

namespace BenBjurstrom\SqliteVecScout\Tests;

use BenBjurstrom\SqliteVecScout\SqliteVecScoutServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Scout\EngineManager;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'BenBjurstrom\\SqliteVecScout\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        $app->singleton(EngineManager::class, function ($app) {
            return new EngineManager($app);
        });

        return [
            SqliteVecScoutServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        config()->set('scout.driver', 'sqlite-vec');
    }
}
