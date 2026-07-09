<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Second line of defense, independent of phpunit.xml's env forcing:
     * RefreshDatabase drops and rebuilds the whole schema on first use.
     * If the app ever resolves to a real (non in-memory-sqlite) connection
     * during a test run — e.g. a docker container baking real DB
     * credentials into its OS environment ahead of Laravel's dotenv
     * loading — this aborts before any schema-dropping code can run,
     * instead of silently wiping real data.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "Refusing to run tests: resolved DB connection is \"{$connection}\" (database=\"{$database}\"), ".
                'not the in-memory sqlite testing DB. Check phpunit.xml env forcing and .env.testing.'
            );
        }
    }
}
