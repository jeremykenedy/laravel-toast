<?php

declare(strict_types=1);

use Jeremykenedy\LaravelToast\Services\ToastManager;

/**
 * Behaviour every renderer has to agree on. Each case here is a defect that
 * reached review, where one renderer honoured a documented setting and another
 * quietly did something else.
 */
function rendererSources(): array
{
    $root = dirname(__DIR__, 2);

    return [
        'tailwind blade'   => $root.'/resources/views/tailwind/blade/toasts.blade.php',
        'bootstrap5 blade' => $root.'/resources/views/bootstrap5/blade/toasts.blade.php',
        'bootstrap4 blade' => $root.'/resources/views/bootstrap4/blade/toasts.blade.php',
        'livewire timer'   => $root.'/resources/views/livewire/partials/timer-script.blade.php',
        'vue'              => $root.'/resources/js/vue/pages/ToastContainer.vue',
        'react'            => $root.'/resources/js/react/pages/ToastContainer.jsx',
        'svelte'           => $root.'/resources/js/svelte/pages/ToastContainer.svelte',
    ];
}

dataset('renderers', fn () => rendererSources());

it('never coerces a zero duration into a default countdown', function (string $file) {
    // config/toast.php documents 0 as "no auto-dismiss". `parseInt(x) || 5000`
    // turns that into a five second timer.
    expect(file_get_contents($file))->not->toContain('dataset.duration) || 5000');
})->with('renderers');

it('guards the timer on a non positive duration', function (string $file) {
    $source = file_get_contents($file);

    expect($source)->toMatch('/duration\s*<=\s*0/');
})->with('renderers');

it('tracks hover and focus as separate pause sources', function (string $file) {
    $source = file_get_contents($file);

    // One shared flag lets focusout resume the countdown while the pointer is
    // still over the toast.
    expect($source)->toContain('hover')
        ->and($source)->toContain('focus');

    $usesSeparateSources = str_contains($source, "'hover'") || str_contains($source, 'hovered');

    expect($usesSeparateSources)->toBeTrue();
})->with('renderers');

it('resumes only once when both sources release', function (string $file) {
    $source = file_get_contents($file);

    $guarded = str_contains($source, 'running || hovered || focused')
        || preg_match('/Object\.keys\((this\.)?holders/', $source) === 1;

    expect($guarded)->toBeTrue();
})->with('renderers');

dataset('blade renderers', ['tailwind', 'bootstrap5', 'bootstrap4']);

it('announces errors assertively and other types politely', function (string $css) {
    config(['toast.css_framework' => $css]);

    app('view')->getFinder()->flush();
    app('view')->replaceNamespace('toast', realpath(__DIR__."/../../resources/views/{$css}/blade"));

    app(ToastManager::class)->success('Quiet');
    $html = view('toast::toasts')->render();

    // role="alert" carries an implicit assertive live region, so politeness has
    // to be stated outright or every toast interrupts the screen reader.
    expect($html)->toContain('role="alert"')
        ->and($html)->toMatch('/aria-live=(")?\{?[^>]*polite/');
})->with('blade renderers');

it('sets the politeness of a livewire toast from its type', function (string $view) {
    $source = file_get_contents(dirname(__DIR__, 2)."/resources/views/livewire/{$view}");

    expect($source)->toContain("'error' ? 'assertive' : 'polite'");
})->with([
    'toast-container.blade.php',
    'bootstrap5/toast-container.blade.php',
    'bootstrap4/toast-container.blade.php',
]);

it('sets the politeness of a javascript toast from its type', function (string $file) {
    expect(file_get_contents($file))->toContain("'error' ? 'assertive' : 'polite'");
})->with([
    'resources/js/vue/pages/ToastContainer.vue',
    'resources/js/react/pages/ToastContainer.jsx',
    'resources/js/svelte/pages/ToastContainer.svelte',
]);

it('dismisses a livewire toast through the component rather than the dom alone', function () {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/partials/timer-script.blade.php');

    // Dropping only the node leaves the toast in $toasts, and the next morph
    // renders it straight back with a fresh timer.
    expect($source)->toContain("component.call('dismiss'")
        ->and($source)->toContain('wire\\\\:id');
});

it('registers the livewire morph hook even when livewire already booted', function () {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/partials/timer-script.blade.php');

    // The partial first renders with the opening toast, which can be long after
    // livewire:initialized fired.
    $position = strpos($source, "document.addEventListener('livewire:initialized'");
    $standalone = strrpos($source, 'registerHook();');

    expect($position)->not->toBeFalse()
        ->and($standalone)->toBeGreaterThan($position);
});

it('reads current toasts from a ref so the react timer cannot go stale', function () {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/js/react/pages/ToastContainer.jsx');

    // The mount effect never reruns, so a dismiss that closed over the initial
    // empty array would skip every exit animation.
    expect($source)->toContain('toastsRef.current.find')
        ->and($source)->not->toContain('const toast = toasts.find');
});

it('keeps the react progress updater free of side effects', function () {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/js/react/pages/ToastContainer.jsx');

    // Strict Mode invokes updaters twice, which would start two timer loops.
    expect($source)->not->toMatch('/setProgress\(\s*prev\s*=>\s*\{[^}]*startTimer/s');
});
