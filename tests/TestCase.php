<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Tests;

use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ToastServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Toast' => \Jeremykenedy\LaravelToast\Facades\Toast::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Force SQLite in-memory -- toast tests NEVER touch a real database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Explicitly disable all external database connections
        $app['config']->set('database.connections.mysql', null);
        $app['config']->set('database.connections.pgsql', null);
        $app['config']->set('database.connections.sqlsrv', null);

        // Toast config
        $app['config']->set('toast.position', 'top-right');
        $app['config']->set('toast.duration', 5000);
        $app['config']->set('toast.max_visible', 5);
        $app['config']->set('toast.session_key', 'toast_notifications');
        $app['config']->set('toast.convert_flash', true);
        $app['config']->set('ui-kit.css_framework', 'tailwind');
        $app['config']->set('ui-kit.frontend', 'blade');

        // Safety: ensure we are in testing environment
        $app['config']->set('app.env', 'testing');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertDatabaseIsSafe();
    }

    /**
     * Hard fail if any real database connection is active.
     * Toast tests are session/view-only -- zero database required.
     */
    private function assertDatabaseIsSafe(): void
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");

        // Only allow SQLite :memory: or null
        if ($connection !== null) {
            $dbDriver = $connection['driver'] ?? 'unknown';
            $dbName = $connection['database'] ?? 'unknown';

            if ($dbDriver !== 'sqlite' || $dbName !== ':memory:') {
                $this->fail(
                    'SAFETY: Toast tests detected a non-memory database connection: '
                    ."{$dbDriver}/{$dbName}. Toast tests must NEVER touch a real database. "
                    .'Only SQLite :memory: is allowed.'
                );
            }
        }
    }
}
