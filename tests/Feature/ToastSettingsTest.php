<?php

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Jeremykenedy\LaravelToast\Models\ToastSetting;
use Jeremykenedy\LaravelToast\Services\ToastManager;
use Jeremykenedy\LaravelToast\Support\ToastSettings;

function toastAdmin(int $id = 1): User
{
    $user = new User();
    $user->id = $id;

    return $user;
}

function migrateToastSettings(): void
{
    (include dirname(__DIR__, 2).'/database/migrations/create_toast_settings_table.php.stub')->up();
}

beforeEach(function () {
    Cache::flush();
    Gate::define('manage-toast-settings', fn ($user) => $user->id === 1);
});

it('is off by default and exposes nothing until enabled', function () {
    $defaults = require dirname(__DIR__, 2).'/config/toast.php';

    expect($defaults['settings']['enabled'])->toBeFalse()
        ->and($defaults['settings']['page'])->toBeFalse()
        ->and($defaults['settings']['middleware'])->toBe(['web', 'auth'])
        ->and($defaults['colors'])->toBe([]);
});

it('requires authentication', function () {
    $this->getJson('/toast/settings/data')->assertUnauthorized();
});

it('denies users the gate rejects', function () {
    $this->actingAs(toastAdmin(2))->getJson('/toast/settings/data')->assertForbidden();
    $this->actingAs(toastAdmin(2))->put('/toast/settings', [])->assertForbidden();
    $this->actingAs(toastAdmin(2))->delete('/toast/settings')->assertForbidden();
});

it('denies everyone when the gate is not defined', function () {
    config(['toast.settings.gate' => 'gate-nobody-defined']);

    $this->actingAs(toastAdmin())->getJson('/toast/settings/data')->assertForbidden();
});

it('denies everyone when the feature is disabled', function () {
    config(['toast.settings.enabled' => false]);

    $this->actingAs(toastAdmin())->getJson('/toast/settings/data')->assertForbidden();
});

it('returns the current settings payload', function () {
    migrateToastSettings();

    $this->actingAs(toastAdmin())->getJson('/toast/settings/data')
        ->assertOk()
        ->assertJsonPath('ready', true)
        ->assertJsonPath('options.position', 'top-right')
        ->assertJsonPath('types', ['success', 'error', 'warning', 'info'])
        ->assertJsonPath('urls.update', url('/toast/settings'));
});

it('saves options and colors, and serves them through config', function () {
    migrateToastSettings();

    $this->actingAs(toastAdmin())->putJson('/toast/settings', [
        'options' => ['position' => 'bottom-left', 'duration' => 8000, 'show_close' => false],
        'colors'  => ['success' => ['light' => ['background' => '#ECFDF5', 'text' => null]]],
    ])->assertOk()
        ->assertJsonPath('options.position', 'bottom-left')
        ->assertJsonPath('colors.success.light.background', '#ecfdf5');

    expect(config('toast.position'))->toBe('bottom-left')
        ->and(config('toast.duration'))->toBe(8000)
        ->and(config('toast.show_close'))->toBeFalse()
        ->and(ToastSetting::query()->count())->toBe(1);

    $toast = app(ToastManager::class)->build('success', 'Saved');

    expect($toast['position'])->toBe('bottom-left')
        ->and($toast['colors_css'])->toContain('background-color:#ecfdf5!important');
});

it('keeps saved options when only colors are sent', function () {
    migrateToastSettings();

    $this->actingAs(toastAdmin())->putJson('/toast/settings', ['options' => ['duration' => 9000]])->assertOk();
    $this->actingAs(toastAdmin())->putJson('/toast/settings', ['colors' => ['info' => ['dark' => ['icon' => '#123456']]]])->assertOk()
        ->assertJsonPath('options.duration', 9000)
        ->assertJsonPath('colors.info.dark.icon', '#123456');
});

it('clears a color when it is submitted empty', function () {
    migrateToastSettings();

    $this->actingAs(toastAdmin())->putJson('/toast/settings', ['colors' => ['error' => ['light' => ['border' => '#aa0000']]]])->assertOk();
    $this->actingAs(toastAdmin())->putJson('/toast/settings', ['colors' => ['error' => ['light' => ['border' => '']]]])->assertOk()
        ->assertJsonPath('colors', []);
});

it('rejects invalid input', function (array $payload, string $field) {
    migrateToastSettings();

    $this->actingAs(toastAdmin())->putJson('/toast/settings', $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'bad hex'          => [['colors' => ['success' => ['light' => ['background' => 'red']]]], 'colors.success.light.background'],
    'css injection'    => [['colors' => ['success' => ['light' => ['background' => '#fff;}body{display:none']]]], 'colors.success.light.background'],
    'unknown type'     => [['colors' => ['danger' => ['light' => ['background' => '#ffffff']]]], 'colors'],
    'unknown part'     => [['colors' => ['success' => ['light' => ['shadow' => '#ffffff']]]], 'colors.success.light'],
    'unknown option'   => [['options' => ['evil' => 'x']], 'options'],
    'bad position'     => [['options' => ['position' => 'middle']], 'options.position'],
    'bad animation'    => [['options' => ['enter_animation' => 'explode']], 'options.enter_animation'],
    'opacity too high' => [['options' => ['opacity' => 2]], 'options.opacity'],
    'negative time'    => [['options' => ['duration' => -1]], 'options.duration'],
]);

it('answers 409 when the table is missing', function () {
    $this->actingAs(toastAdmin())->putJson('/toast/settings', ['options' => ['duration' => 1000]])
        ->assertStatus(409);
    $this->actingAs(toastAdmin())->deleteJson('/toast/settings')->assertStatus(409);
});

it('resets to config defaults', function () {
    migrateToastSettings();

    $this->actingAs(toastAdmin())->putJson('/toast/settings', [
        'options' => ['position' => 'top-center'],
        'colors'  => ['success' => ['light' => ['background' => '#ecfdf5']]],
    ])->assertOk();

    $this->actingAs(toastAdmin())->deleteJson('/toast/settings')->assertOk()
        ->assertJsonPath('options.position', 'top-right')
        ->assertJsonPath('colors', []);

    expect(ToastSetting::query()->count())->toBe(0)
        ->and(config('toast.colors'))->toBe([]);
});

it('redirects back with a toast for a plain form post', function () {
    migrateToastSettings();

    $this->actingAs(toastAdmin())->from('/somewhere')->put('/toast/settings', ['options' => ['duration' => 4000]])
        ->assertRedirect('/somewhere')
        ->assertSessionHas('toast_notifications');
});

it('applies saved settings over config on boot', function () {
    migrateToastSettings();
    ToastSetting::query()->create(['key' => 'global', 'value' => ['options' => ['position' => 'bottom-center'], 'colors' => ['info' => ['light' => ['text' => '#101010']]]]]);

    ToastSettings::apply();

    expect(config('toast.position'))->toBe('bottom-center')
        ->and(config('toast.colors.info.light.text'))->toBe('#101010');
});

it('ignores saved settings while the feature is disabled', function () {
    migrateToastSettings();
    ToastSetting::query()->create(['key' => 'global', 'value' => ['options' => ['position' => 'bottom-center']]]);
    config(['toast.settings.enabled' => false]);

    ToastSettings::apply();

    expect(config('toast.position'))->toBe('top-right');
});

it('does not break when the table has not been migrated', function () {
    ToastSettings::apply();

    expect(config('toast.position'))->toBe('top-right');
});

it('merges colors from config into every toast payload', function () {
    config(['toast.colors' => ['warning' => ['light' => ['track' => '#fef3c7']]]]);

    expect(app(ToastManager::class)->build('warning', 'x')['colors_css'])
        ->toContain('[data-toast-part="track"]{background-color:#fef3c7!important}');
});

it('omits the stylesheet when no colors are set', function () {
    expect(app(ToastManager::class)->build('info', 'x')['colors_css'])->toBe('');
});

it('renders the app-owned page view when one is configured', function () {
    migrateToastSettings();
    $views = sys_get_temp_dir().'/toast-own-'.uniqid();
    mkdir($views.'/toast', 0777, true);
    file_put_contents($views.'/toast/settings.blade.php', '<p>my own page</p>');
    app('view')->addLocation($views);
    config(['toast.settings.view' => 'toast.settings']);

    $this->actingAs(toastAdmin())->get('/toast/settings')->assertOk()->assertSee('my own page');
});

it('renders the settings page in each css framework', function (string $framework, string $marker, string $absent) {
    migrateToastSettings();
    app('view')->replaceNamespace('toast', [realpath(__DIR__.'/../../resources/views/'.$framework.'/blade')]);

    $this->actingAs(toastAdmin())->get('/toast/settings')
        ->assertOk()
        ->assertSee('data-toast-settings', false)
        ->assertSee('colors[success][light][background]', false)
        ->assertSee('data-toast-type="warning"', false)
        ->assertSee('options[position]', false)
        ->assertSee($marker, false)
        ->assertDontSee($absent, false);
})->with([
    'tailwind'   => ['tailwind', 'dark:border-gray-700', 'form-control'],
    'bootstrap5' => ['bootstrap5', 'form-control-color', 'dark:border-gray-700'],
    'bootstrap4' => ['bootstrap4', 'custom-control custom-checkbox', 'dark:border-gray-700'],
]);
