<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;
use Jeremykenedy\LaravelToast\Services\ToastManager;
use Jeremykenedy\LaravelToast\Support\ToastAnimations;

/**
 * `vendor:publish --tag=toast-views` copies the whole tree, so an override
 * lands at views/vendor/toast/{framework}/blade. Laravel only checks
 * views/vendor/toast on its own, so the nested path is registered explicitly.
 * Without a test the ordering can regress and publishing silently does nothing.
 */
/**
 * addNamespace appends, so re-booting the provider on top of the hints the
 * test app already registered would leave the packaged path first. Clear them
 * so the assertion measures the provider's own ordering.
 */
function rebootViews(): void
{
    app('view')->getFinder()->flush();
    app('view')->replaceNamespace('toast', []);
    app('view')->replaceNamespace('toast-livewire', []);

    (new ToastServiceProvider(app()))->boot();
}

function publishView(string $relative, string $contents): string
{
    $path = resource_path('views/vendor/toast/'.$relative);

    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    file_put_contents($path, $contents);

    return $path;
}

afterEach(function () {
    $root = resource_path('views/vendor/toast');

    if (!is_dir($root)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($root);
});

it('lets a published blade override win over the packaged view', function () {
    publishView('tailwind/blade/toasts.blade.php', 'PUBLISHED OVERRIDE WON');

    config(['toast.css_framework' => 'tailwind']);
    rebootViews();

    app(ToastManager::class)->success('Ignored by the override');

    expect(view('toast::toasts')->render())->toContain('PUBLISHED OVERRIDE WON');
});

it('registers the nested published path for each css framework', function (string $css) {
    publishView($css.'/blade/toasts.blade.php', "OVERRIDE FOR {$css}");

    config(['toast.css_framework' => $css]);
    rebootViews();

    expect(view('toast::toasts')->render())->toContain("OVERRIDE FOR {$css}");
})->with(['tailwind', 'bootstrap5', 'bootstrap4']);

it('lets a published livewire override win', function () {
    publishView('livewire/toast-container.blade.php', '<div>PUBLISHED LIVEWIRE OVERRIDE</div>');

    config(['toast.css_framework' => 'tailwind']);
    rebootViews();

    expect(view('toast-livewire::toast-container', ['toasts' => []])->render())
        ->toContain('PUBLISHED LIVEWIRE OVERRIDE');
});

it('falls back to the packaged view when nothing is published', function () {
    config(['toast.css_framework' => 'tailwind']);
    rebootViews();

    app(ToastManager::class)->success('From the package');

    expect(view('toast::toasts')->render())->toContain('From the package');
});

it('publishes only tags whose contents still resolve where they land', function () {
    $groups = ServiceProvider::$publishGroups;

    // A `toast-js` tag published the components to resources/js/vendor/toast,
    // where their '../../../css/toast-animations.css' import resolved to
    // resources/js/vendor/css and broke the consuming bundler build.
    expect(array_keys($groups))
        ->toContain('toast-config', 'toast-views', 'toast-lang', 'toast-css')
        ->and(array_keys($groups))->not->toContain('toast-js');
});

it('keeps the published stylesheet reachable from the documented copy path', function () {
    $groups = ServiceProvider::$publishGroups;

    expect($groups['toast-css'])->toHaveCount(1);

    $source = array_key_first($groups['toast-css']);

    expect(is_file($source))->toBeTrue()
        ->and(realpath($source))->toBe(realpath(ToastAnimations::path()));
});
