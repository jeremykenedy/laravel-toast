<?php

declare(strict_types=1);

use Jeremykenedy\LaravelToast\Support\ToastAnimations;

it('ships an enter and exit keyframe for every documented animation', function () {
    $css = ToastAnimations::css();

    $missing = [];
    foreach (ToastAnimations::NAMES as $name) {
        if (!str_contains($css, "@keyframes toast-{$name}{")) {
            $missing[] = "toast-{$name}";
        }
        if (!str_contains($css, "@keyframes toast-enter-{$name}{")) {
            $missing[] = "toast-enter-{$name}";
        }
    }

    expect($missing)->toBe([]);
});

it('declares exactly the animations the stylesheet defines', function () {
    preg_match_all('/@keyframes toast-(?!enter-)([a-z-]+)\{/', ToastAnimations::css(), $matches);

    $inStylesheet = array_unique($matches[1]);
    sort($inStylesheet);

    $declared = ToastAnimations::NAMES;
    sort($declared);

    expect($inStylesheet)->toBe($declared);
});

it('offers none alongside the 49 named animations', function () {
    expect(ToastAnimations::names())->toHaveCount(50)
        ->and(ToastAnimations::names()[0])->toBe('none')
        ->and(ToastAnimations::NAMES)->toHaveCount(49);
});

it('accepts known animation names and rejects unknown ones', function () {
    expect(ToastAnimations::supports('none'))->toBeTrue()
        ->and(ToastAnimations::supports('slide-left'))->toBeTrue()
        ->and(ToastAnimations::supports('wobble-center'))->toBeTrue()
        ->and(ToastAnimations::supports('barrel-roll'))->toBeFalse()
        ->and(ToastAnimations::supports(''))->toBeFalse()
        ->and(ToastAnimations::supports(null))->toBeFalse();
});

it('strips comments so the inlined stylesheet carries no documentation weight', function () {
    expect(ToastAnimations::css())->not->toContain('/*')
        ->and(ToastAnimations::css())->not->toContain('*/');
});

it('honours the reduced motion preference', function () {
    expect(ToastAnimations::css())->toContain('prefers-reduced-motion: reduce')
        ->and(ToastAnimations::css())->toContain('[data-toast-id]')
        ->and(ToastAnimations::css())->toContain('[id^="lw-toast-"]');
});

it('wraps the stylesheet in an identified style tag', function () {
    $tag = ToastAnimations::styleTag();

    expect($tag)->toStartWith('<style id="toast-animations">')
        ->and($tag)->toEndWith('</style>')
        ->and($tag)->toContain(ToastAnimations::css());
});

it('caches the stylesheet after the first read', function () {
    ToastAnimations::flush();

    expect(ToastAnimations::css())->toBe(ToastAnimations::css())
        ->and(ToastAnimations::css())->not->toBeEmpty();
});
