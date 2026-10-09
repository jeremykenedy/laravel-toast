<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Jeremykenedy\LaravelToast\Livewire\ToastSettings;
use Jeremykenedy\LaravelToast\Models\ToastSetting;
use Livewire\Livewire;

function livewireAdmin(int $id = 1): User
{
    $user = new User();
    $user->id = $id;

    return $user;
}

beforeEach(function () {
    Cache::flush();
    Gate::define('manage-toast-settings', fn ($user) => $user->id === 1);
    (include dirname(__DIR__, 2).'/database/migrations/create_toast_settings_table.php.stub')->up();
});

it('registers the toast-settings component', function () {
    expect(Livewire::new('toast-settings'))->toBeInstanceOf(ToastSettings::class);
});

it('is forbidden for users the gate rejects', function () {
    Livewire::actingAs(livewireAdmin(2))->test(ToastSettings::class)->assertForbidden();
});

it('starts with every color unset', function () {
    Livewire::actingAs(livewireAdmin())->test(ToastSettings::class)
        ->assertSet('colors.success.light.background', '')
        ->assertSet('colors.info.dark.track', '')
        ->assertSet('options.position', 'top-right');
});

it('saves colors and options, then reloads them', function () {
    Livewire::actingAs(livewireAdmin())->test(ToastSettings::class)
        ->set('colors.success.light.background', '#ECFDF5')
        ->set('options.position', 'bottom-right')
        ->set('options.show_close', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-success')
        ->assertSet('colors.success.light.background', '#ecfdf5');

    expect(ToastSetting::query()->first()->value['colors']['success']['light']['background'])->toBe('#ecfdf5')
        ->and(config('toast.position'))->toBe('bottom-right');
});

it('rejects an invalid color', function () {
    Livewire::actingAs(livewireAdmin())->test(ToastSettings::class)
        ->set('colors.error.light.border', 'javascript:alert(1)')
        ->call('save')
        ->assertHasErrors('colors.error.light.border');

    expect(ToastSetting::query()->count())->toBe(0);
});

it('clears one color back to Default', function () {
    Livewire::actingAs(livewireAdmin())->test(ToastSettings::class)
        ->set('colors.warning.dark.icon', '#ffcc00')
        ->call('clearColor', 'warning', 'dark', 'icon')
        ->assertSet('colors.warning.dark.icon', '');
});

it('ignores clearing an unknown color slot', function () {
    Livewire::actingAs(livewireAdmin())->test(ToastSettings::class)
        ->call('clearColor', 'danger', 'light', 'background')
        ->assertSet('colors', fn (array $colors) => !isset($colors['danger']));
});

it('resets everything to config defaults', function () {
    Livewire::actingAs(livewireAdmin())->test(ToastSettings::class)
        ->set('colors.info.light.text', '#101010')
        ->call('save')
        ->call('resetAll')
        ->assertSet('colors.info.light.text', '')
        ->assertDispatched('toast-success');

    expect(ToastSetting::query()->count())->toBe(0);
});

it('builds the preview stylesheet from unsaved edits', function () {
    Livewire::actingAs(livewireAdmin())->test(ToastSettings::class)
        ->set('colors.success.light.background', '#abcdef')
        ->assertSeeHtml('[data-toast-preview="light"] [data-laravel-toast][data-toast-type="success"]{background-color:#abcdef!important}');
});

it('hides preview parts that are switched off', function () {
    Livewire::actingAs(livewireAdmin())->test(ToastSettings::class)
        ->set('options.show_icons', false)
        ->assertSeeHtml('[data-toast-preview] [data-toast-part="icon"]{display:none}');
});

it('reports a missing table instead of failing', function () {
    Schema::dropIfExists('toast_settings');

    Livewire::actingAs(livewireAdmin())->test(ToastSettings::class)
        ->call('save')
        ->assertDispatched('toast-error');
});

it('renders in each css framework', function (string $framework, string $marker) {
    $root = realpath(__DIR__.'/../../resources/views/livewire');
    $paths = $framework === 'tailwind' ? [$root] : [$root.'/'.$framework, $root];
    app('view')->replaceNamespace('toast-livewire', $paths);

    Livewire::actingAs(livewireAdmin())->test(ToastSettings::class)
        ->assertSeeHtml($marker)
        ->assertSeeHtml('wire:model.live.debounce.150ms="colors.success.light.background"')
        ->assertSeeHtml('wire:click="resetAll"');
})->with([
    'tailwind'   => ['tailwind', 'dark:border-gray-700'],
    'bootstrap5' => ['bootstrap5', 'form-control-color'],
    'bootstrap4' => ['bootstrap4', 'custom-control custom-checkbox'],
]);
