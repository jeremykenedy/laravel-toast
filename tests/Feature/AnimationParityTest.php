<?php

declare(strict_types=1);

use Jeremykenedy\LaravelToast\Services\ToastManager;
use Jeremykenedy\LaravelToast\Support\ToastAnimations;

/**
 * Every renderer must offer the same animations. Before the shared stylesheet
 * the Livewire view carried 13 of the 49 and the JavaScript components carried
 * none, so a configured animation silently did nothing outside Blade.
 */
function renderedKeyframes(string $html): array
{
    preg_match_all('/@keyframes (toast-[a-z-]+)\{/', $html, $matches);

    $names = array_unique($matches[1]);
    sort($names);

    return $names;
}

function expectedKeyframes(): array
{
    $names = [];
    foreach (ToastAnimations::NAMES as $name) {
        $names[] = "toast-{$name}";
        $names[] = "toast-enter-{$name}";
    }
    sort($names);

    return $names;
}

dataset('css frameworks', ['tailwind', 'bootstrap5', 'bootstrap4']);

it('renders all 98 keyframes from every blade view', function (string $css) {
    config(['toast.css_framework' => $css]);

    $this->app->make('view')->getFinder()->flush();
    $this->app->make('view')->replaceNamespace('toast', realpath(__DIR__."/../../resources/views/{$css}/blade"));

    app(ToastManager::class)->success('Parity');

    expect(renderedKeyframes(view('toast::toasts')->render()))->toBe(expectedKeyframes());
})->with('css frameworks');

it('renders all 98 keyframes from every livewire view', function (string $css) {
    $path = $css === 'tailwind'
        ? realpath(__DIR__.'/../../resources/views/livewire')
        : realpath(__DIR__."/../../resources/views/livewire/{$css}");

    $this->app->make('view')->getFinder()->flush();
    $this->app->make('view')->replaceNamespace('toast-livewire', [
        $path,
        realpath(__DIR__.'/../../resources/views/livewire'),
    ]);

    $html = view('toast-livewire::toast-container', [
        'toasts' => [app(ToastManager::class)->build('info', 'Parity')],
    ])->render();

    expect(renderedKeyframes($html))->toBe(expectedKeyframes());
})->with('css frameworks');

dataset('javascript components', [
    'vue'    => 'resources/js/vue/pages/ToastContainer.vue',
    'react'  => 'resources/js/react/pages/ToastContainer.jsx',
    'svelte' => 'resources/js/svelte/pages/ToastContainer.svelte',
]);

it('imports the shared stylesheet into every javascript component', function (string $file) {
    $source = file_get_contents(dirname(__DIR__, 2).'/'.$file);

    expect($source)->toContain("'../../../css/toast-animations.css'");
})->with('javascript components');

it('resolves the stylesheet path each javascript component imports', function (string $file) {
    $componentDirectory = dirname(dirname(__DIR__, 2).'/'.$file);

    expect(realpath($componentDirectory.'/../../../css/toast-animations.css'))
        ->toBe(realpath(ToastAnimations::path()));
})->with('javascript components');

it('leaves no unresolvable import in the javascript components', function (string $file) {
    $source = file_get_contents(dirname(__DIR__, 2).'/'.$file);

    // "@/..." is an application alias. The package cannot rely on one existing,
    // and an unresolved alias breaks the consuming bundler build.
    expect($source)->not->toMatch('#from\s+["\']@/#');
})->with('javascript components');
