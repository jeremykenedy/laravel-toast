<?php

use Jeremykenedy\LaravelToast\Support\ToastColors;

it('only accepts hex colors and expands short hex', function () {
    expect(ToastColors::hex('#ABC'))->toBe('#aabbcc')
        ->and(ToastColors::hex('#1A2b3C'))->toBe('#1a2b3c')
        ->and(ToastColors::hex('red'))->toBeNull()
        ->and(ToastColors::hex('#12'))->toBeNull()
        ->and(ToastColors::hex('#fff;}body{display:none'))->toBeNull()
        ->and(ToastColors::hex(null))->toBeNull();
});

it('drops unknown types, modes, parts and invalid values', function () {
    $clean = ToastColors::normalize([
        'success' => ['light' => ['background' => '#ECFDF5', 'bogus' => '#000000'], 'night' => ['text' => '#111111']],
        'other'   => ['light' => ['text' => '#111111']],
        'error'   => ['dark' => ['text' => 'url(x)']],
    ]);

    expect($clean)->toBe(['success' => ['light' => ['background' => '#ecfdf5']]]);
});

it('generates nothing when no color is set', function () {
    expect(ToastColors::css([]))->toBe('')
        ->and(ToastColors::css(null))->toBe('')
        ->and(ToastColors::styleTag([]))->toBe('');
});

it('generates light and dark rules for each part', function () {
    $css = ToastColors::css([
        'success' => [
            'light' => ['background' => '#ecfdf5', 'icon' => '#10b981', 'progress' => '#059669'],
            'dark'  => ['text' => '#d1fae5'],
        ],
    ]);

    expect($css)
        ->toContain('[data-laravel-toast][data-toast-type="success"]{background-color:#ecfdf5!important}')
        ->toContain('[data-laravel-toast][data-toast-type="success"] [data-toast-part="icon"], [data-laravel-toast][data-toast-type="success"] [data-toast-part="icon"] svg{color:#10b981!important}')
        ->toContain('[data-laravel-toast][data-toast-type="success"] [data-toast-part="bar"]{background-color:#059669!important}')
        ->toContain(':where(.dark, [data-bs-theme="dark"]) [data-laravel-toast][data-toast-type="success"]{color:#d1fae5!important}');
});

it('accepts custom scopes for the preview', function () {
    $css = ToastColors::css(['info' => ['dark' => ['border' => '#ffffff']]], ['dark' => '[data-toast-preview="dark"] ']);

    expect($css)->toBe('[data-toast-preview="dark"] [data-laravel-toast][data-toast-type="info"]{border-color:#ffffff!important}');
});

it('makes Bootstrap close buttons follow a custom text color', function () {
    $css = ToastColors::css(['success' => ['light' => ['text' => '#115e59']]]);

    expect($css)
        ->toContain('[data-laravel-toast]{--toast-close-icon:url(')
        ->toContain('[data-laravel-toast][data-toast-type="success"] .close{color:inherit!important}')
        ->toContain('[data-laravel-toast][data-toast-type="success"] .btn-close{color:inherit!important;filter:none!important;background-image:none!important;background-color:currentColor!important')
        ->and(substr_count($css, '--toast-close-icon:url('))->toBe(1);
});

it('leaves close buttons alone when no text color is set', function () {
    expect(ToastColors::css(['success' => ['light' => ['background' => '#ecfdf5']]]))
        ->not->toContain('.btn-close')
        ->not->toContain('--toast-close-icon');
});

it('wraps css in a style tag', function () {
    expect(ToastColors::styleTag(['error' => ['light' => ['border' => '#ff0000']]]))
        ->toStartWith('<style id="toast-colors">')
        ->toEndWith('</style>');
});
