<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup permissions before any test runs.
     * This ensures all permissions are properly synced from enums.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Only sync permissions if we're using the database
        if ($this->app->bound('db') && $this->app['db']->connection()->getDatabaseName()) {
            // Sync all permissions from Domain enums
            Artisan::call('permission:sync --path=Domain');
        }
    }
}
