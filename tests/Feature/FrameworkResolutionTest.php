<?php

declare(strict_types=1);

use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;
use Jeremykenedy\LaravelToast\Services\ToastManager;

/**
 * `toast.css_framework` was added so the package resolves views on its own,
 * without jeremykenedy/laravel-ui-kit installed. Applications already driven by
 * ui-kit must keep resolving exactly as they did, which is what the fallback
 * cases below lock in.
 */
beforeEach(function () {
    config(['toast.css_framework' => null, 'toast.frontend' => null]);
    config(['ui-kit.css_framework' => null, 'ui-kit.frontend' => null]);
});

it('falls back to ui-kit when the toast setting is unset', function () {
    config(['ui-kit.css_framework' => 'bootstrap5']);

    expect(ToastServiceProvider::cssFramework())->toBe('bootstrap5');
});

it('keeps deferring to ui-kit when the toast key is missing entirely', function () {
    config()->offsetUnset('toast.css_framework');
    config(['ui-kit.css_framework' => 'bootstrap4']);

    expect(ToastServiceProvider::cssFramework())->toBe('bootstrap4');
});

it('lets the toast setting take precedence over ui-kit', function () {
    config(['ui-kit.css_framework' => 'bootstrap5', 'toast.css_framework' => 'bootstrap4']);

    expect(ToastServiceProvider::cssFramework())->toBe('bootstrap4');
});

it('resolves standalone with neither ui-kit nor an explicit setting', function () {
    expect(ToastServiceProvider::cssFramework())->toBe('tailwind')
        ->and(ToastServiceProvider::frontend())->toBe('blade');
});

it('rejects an unknown css framework rather than looking for its directory', function () {
    config(['toast.css_framework' => 'material']);

    expect(ToastServiceProvider::cssFramework())->toBe('tailwind');
});

it('rejects an unknown frontend', function () {
    config(['toast.frontend' => 'angular']);

    expect(ToastServiceProvider::frontend())->toBe('blade');
});

it('applies the same fallback order to the frontend', function () {
    config(['ui-kit.frontend' => 'vue']);
    expect(ToastServiceProvider::frontend())->toBe('vue');

    config(['toast.frontend' => 'svelte']);
    expect(ToastServiceProvider::frontend())->toBe('svelte');
});

it('treats an empty string as unset so a blank env value does not lock out ui-kit', function () {
    config(['toast.css_framework' => '', 'ui-kit.css_framework' => 'bootstrap5']);

    expect(ToastServiceProvider::cssFramework())->toBe('bootstrap5');
});

it('exposes the framework lists the commands validate against', function () {
    expect(ToastServiceProvider::CSS_FRAMEWORKS)->toBe(['tailwind', 'bootstrap5', 'bootstrap4'])
        ->and(ToastServiceProvider::FRONTENDS)->toBe(['blade', 'livewire', 'vue', 'react', 'svelte']);
});

it('ships a view directory for every css framework it accepts', function () {
    foreach (ToastServiceProvider::CSS_FRAMEWORKS as $css) {
        expect(is_dir(dirname(__DIR__, 2)."/resources/views/{$css}/blade"))->toBeTrue();
    }
});

it('renders bootstrap markup standalone when only TOAST_CSS is set', function () {
    config(['toast.css_framework' => 'bootstrap5']);

    $this->app->make('view')->getFinder()->flush();
    $this->app->make('view')->replaceNamespace('toast', realpath(__DIR__.'/../../resources/views/bootstrap5/blade'));

    app(ToastManager::class)->success('Standalone');
    $html = view('toast::toasts')->render();

    expect($html)->toContain('text-bg-success')
        ->and($html)->not->toContain('bg-green-50');
});

it('does not fall through to ui-kit when the toast setting is set but invalid', function () {
    config(['toast.css_framework' => 'material', 'ui-kit.css_framework' => 'bootstrap5']);
    expect(ToastServiceProvider::cssFramework())->toBe('tailwind');

    config(['toast.frontend' => 'angular', 'ui-kit.frontend' => 'vue']);
    expect(ToastServiceProvider::frontend())->toBe('blade');
});

it('does not pin toast behind a ui-kit switch', function () {
    // Writing TOAST_CSS in a ui-kit application would win over ui-kit forever
    // after, so the kit would no longer be able to move toast with it.
    config(['ui-kit.css_framework' => 'tailwind', 'ui-kit.frontend' => 'blade']);

    $this->artisan('toast:switch', ['--css' => 'bootstrap5'])->assertSuccessful();

    expect(config('toast.css_framework'))->toBeNull()
        ->and(config('ui-kit.css_framework'))->toBe('bootstrap5')
        ->and(ToastServiceProvider::cssFramework())->toBe('bootstrap5');
});

it('uses its own setting when ui-kit is not installed', function () {
    config(['ui-kit' => null]);

    $this->artisan('toast:switch', ['--frontend' => 'vue'])->assertSuccessful();

    expect(config('toast.frontend'))->toBe('vue')
        ->and(ToastServiceProvider::frontend())->toBe('vue');
});
