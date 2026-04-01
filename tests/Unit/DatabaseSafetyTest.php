<?php

/**
 * These tests guarantee that the toast package NEVER touches a real database.
 * Toast is a session/view-only package -- zero database required.
 */
it('database driver is sqlite', function () {
    expect(config('database.default'))->toBe('testing');
    expect(config('database.connections.testing.driver'))->toBe('sqlite');
});

it('database is :memory: only', function () {
    expect(config('database.connections.testing.database'))->toBe(':memory:');
});

it('mysql connection is disabled', function () {
    expect(config('database.connections.mysql'))->toBeNull();
});

it('pgsql connection is disabled', function () {
    expect(config('database.connections.pgsql'))->toBeNull();
});

it('sqlsrv connection is disabled', function () {
    expect(config('database.connections.sqlsrv'))->toBeNull();
});

it('environment is testing', function () {
    expect(app()->environment())->toBe('testing');
});

it('is not running in production', function () {
    expect(app()->environment('production'))->toBeFalse();
});
