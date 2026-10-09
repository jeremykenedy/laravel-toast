<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    foreach (File::glob(database_path('migrations/*_create_toast_settings_table.php')) as $file) {
        File::delete($file);
    }

    File::delete(resource_path('views/toast/settings.blade.php'));
});

function publishedSettingsMigrations(): array
{
    return File::glob(database_path('migrations/*_create_toast_settings_table.php'));
}

it('leaves the settings page alone on a plain install', function () {
    $this->artisan('toast:install', ['--force' => true, '--no-interaction' => true])->assertSuccessful();

    expect(publishedSettingsMigrations())->toBe([])
        ->and(File::exists(resource_path('views/toast/settings.blade.php')))->toBeFalse();
});

it('publishes the migration with --settings', function () {
    $this->artisan('toast:install', ['--force' => true, '--no-interaction' => true, '--settings' => true])
        ->expectsOutputToContain('Notification settings enabled.')
        ->expectsOutputToContain('manage-toast-settings')
        ->assertSuccessful();

    expect(publishedSettingsMigrations())->toHaveCount(1)
        ->and(File::exists(resource_path('views/toast/settings.blade.php')))->toBeFalse()
        ->and(config('toast.settings.enabled'))->toBeTrue()
        ->and(config('toast.settings.page'))->toBeFalse();
});

it('does not publish a second migration when one exists', function () {
    $options = ['--force' => true, '--no-interaction' => true, '--settings' => true];

    $this->artisan('toast:install', $options)->assertSuccessful();
    $this->artisan('toast:update', ['--settings' => true])->assertSuccessful();

    expect(publishedSettingsMigrations())->toHaveCount(1);
});

it('publishes the page and records layout, section, middleware and gate', function () {
    $views = sys_get_temp_dir().'/toast-layouts-'.uniqid();
    File::ensureDirectoryExists($views.'/layouts');
    File::put($views.'/layouts/app.blade.php', '<html>@yield("body")</html>');
    app('view')->addLocation($views);

    $this->artisan('toast:install', [
        '--force'               => true,
        '--no-interaction'      => true,
        '--settings-page'       => true,
        '--settings-layout'     => 'layouts.app',
        '--settings-section'    => 'body',
        '--settings-middleware' => 'web,auth,verified',
        '--settings-gate'       => 'edit-toasts',
    ])->assertSuccessful();

    expect(File::exists(resource_path('views/toast/settings.blade.php')))->toBeTrue()
        ->and(config('toast.settings.page'))->toBeTrue()
        ->and(config('toast.settings.view'))->toBe('toast.settings')
        ->and(config('toast.settings.layout'))->toBe('layouts.app')
        ->and(config('toast.settings.section'))->toBe('body')
        ->and(config('toast.settings.middleware'))->toBe(['web', 'auth', 'verified'])
        ->and(config('toast.settings.gate'))->toBe('edit-toasts');

    File::deleteDirectory($views);
});

it('rejects a layout that does not exist before changing anything', function () {
    $this->artisan('toast:install', [
        '--force'           => true,
        '--no-interaction'  => true,
        '--settings-page'   => true,
        '--settings-layout' => 'layouts.nowhere',
    ])->expectsOutputToContain('Layout view not found')->assertFailed();

    expect(publishedSettingsMigrations())->toBe([]);
});

it('rejects an invalid middleware list', function () {
    $this->artisan('toast:install', [
        '--force'               => true,
        '--no-interaction'      => true,
        '--settings'            => true,
        '--settings-middleware' => 'web; rm -rf',
    ])->assertFailed();

    expect(publishedSettingsMigrations())->toBe([]);
});

it('runs the settings step from toast:update without asking for frameworks', function () {
    $this->artisan('toast:install', ['--force' => true, '--no-interaction' => true])->assertSuccessful();

    $this->artisan('toast:update', ['--settings' => true])
        ->expectsOutputToContain('Notification settings enabled.')
        ->assertSuccessful();

    expect(publishedSettingsMigrations())->toHaveCount(1);
});

it('validates settings options on toast:update', function () {
    $this->artisan('toast:install', ['--force' => true, '--no-interaction' => true])->assertSuccessful();

    $this->artisan('toast:update', ['--settings-layout' => 'layouts.nowhere'])->assertFailed();
});

it('shows the include hint when no page is requested', function () {
    $this->artisan('toast:install', ['--force' => true, '--no-interaction' => true, '--settings' => true])
        ->expectsOutputToContain('@toastSettings')
        ->assertSuccessful();
});
