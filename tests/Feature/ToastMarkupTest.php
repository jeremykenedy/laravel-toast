<?php

declare(strict_types=1);

use Jeremykenedy\LaravelToast\Services\ToastManager;

function renderFramework(string $css): string
{
    config(['toast.css_framework' => $css]);

    app('view')->getFinder()->flush();
    app('view')->replaceNamespace('toast', realpath(__DIR__."/../../resources/views/{$css}/blade"));

    return view('toast::toasts')->render();
}

dataset('css frameworks', ['tailwind', 'bootstrap5', 'bootstrap4']);

it('lets clicks through the fixed container and catches them on the toast', function (string $css) {
    app(ToastManager::class)->info('Clickthrough');

    // The container spans a fixed column. Without this it swallows every click
    // in that column, including on the page underneath.
    $styles = str_replace(': ', ':', renderFramework($css));

    expect($styles)->toContain('pointer-events:none')
        ->and($styles)->toContain('pointer-events:auto');
})->with('css frameworks');

it('keeps the toast inside the viewport on a narrow screen', function (string $css) {
    app(ToastManager::class)->info('Narrow');

    expect(renderFramework($css))->toContain('100vw - 1rem');
})->with('css frameworks');

it('marks errors as assertive and everything else as polite', function (string $css) {
    app(ToastManager::class)->error('Broke');
    expect(renderFramework($css))->toContain('aria-live="assertive"');

    app(ToastManager::class)->clear();
    app(ToastManager::class)->success('Fine');
    expect(renderFramework($css))->not->toContain('aria-live="assertive"');
})->with(['bootstrap5', 'bootstrap4']);

it('hides decorative icons from assistive technology', function (string $css) {
    app(ToastManager::class)->success('Icon');

    expect(renderFramework($css))->toContain('aria-hidden="true"');
})->with('css frameworks');

it('labels the dismiss button from the translation file', function (string $css) {
    app()->setLocale('es');
    app(ToastManager::class)->info('Labelled');

    expect(renderFramework($css))->toContain(__('toast::toast.dismiss'));
})->with('css frameworks');

it('closes a bootstrap toast without the bootstrap javascript bundle', function (string $css, string $selector) {
    app(ToastManager::class)->info('Closable');

    $html = renderFramework($css);

    expect($html)->toContain($selector)
        ->and($html)->toContain("addEventListener('click'");
})->with([
    ['bootstrap5', '.btn-close'],
    ['bootstrap4', '.close'],
]);

it('guards against dismissing the same toast twice', function (string $css) {
    app(ToastManager::class)->info('Double dismiss');

    expect(renderFramework($css))->toContain('toastDismissing');
})->with(['bootstrap5', 'bootstrap4']);

it('pauses the countdown on keyboard focus as well as hover', function (string $css) {
    app(ToastManager::class)->info('Focusable');

    expect(renderFramework($css))->toContain('focusin');
})->with('css frameworks');

it('gives every tailwind colour utility a dark counterpart', function () {
    app(ToastManager::class)->success('S')->error('E')->warning('W')->info('I');

    $html = renderFramework('tailwind');

    // Light and dark use different shades, so pair on utility and colour
    // family rather than on the exact class.
    preg_match_all('/(?<![\w:-])((?:bg|text|border)-(?:green|red|amber|blue))-\d{2,3}/', $html, $matches);

    $undarkened = [];
    foreach (array_unique($matches[1]) as $utility) {
        if (!str_contains($html, 'dark:'.$utility.'-')) {
            $undarkened[] = $utility;
        }
    }

    expect($undarkened)->toBe([]);
});

it('keeps each css framework free of the other frameworks classes', function () {
    app(ToastManager::class)->success('Isolation');
    $tailwind = renderFramework('tailwind');

    app(ToastManager::class)->clear();
    app(ToastManager::class)->success('Isolation');
    $bootstrap5 = renderFramework('bootstrap5');

    app(ToastManager::class)->clear();
    app(ToastManager::class)->success('Isolation');
    $bootstrap4 = renderFramework('bootstrap4');

    expect($tailwind)->not->toContain('alert-success')
        ->and($tailwind)->not->toContain('text-bg-success')
        ->and($bootstrap5)->not->toContain('bg-green-50')
        ->and($bootstrap5)->not->toContain('x-data')
        ->and($bootstrap4)->not->toContain('bg-green-50')
        ->and($bootstrap4)->not->toContain('x-data');
});

it('renders nothing at all when there are no toasts', function (string $css) {
    expect(trim(renderFramework($css)))->toBeEmpty();
})->with('css frameworks');
