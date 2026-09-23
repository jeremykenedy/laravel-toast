<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Support;

/**
 * Reads the shared animation keyframes that every renderer draws from.
 *
 * Blade and Livewire inline the stylesheet. Vue, React and Svelte import the
 * same file through their bundler.
 */
final class ToastAnimations
{
    /**
     * Animation families. Each has a `toast-{name}` exit keyframe and a
     * `toast-enter-{name}` enter keyframe.
     *
     * @var list<string>
     */
    public const NAMES = [
        'fade', 'fade-center',
        'slide', 'slide-left', 'slide-right', 'slide-top', 'slide-bottom',
        'bounce', 'bounce-left', 'bounce-right', 'bounce-top', 'bounce-bottom', 'bounce-center',
        'shrink', 'shrink-left', 'shrink-right', 'shrink-top', 'shrink-bottom', 'shrink-center',
        'flip', 'flip-left', 'flip-right', 'flip-top', 'flip-bottom', 'flip-center',
        'spin', 'spin-left', 'spin-right', 'spin-top', 'spin-bottom', 'spin-center',
        'grow', 'grow-left', 'grow-right', 'grow-top', 'grow-bottom', 'grow-center',
        'slam', 'slam-left', 'slam-right', 'slam-top', 'slam-bottom', 'slam-center',
        'wobble', 'wobble-left', 'wobble-right', 'wobble-top', 'wobble-bottom', 'wobble-center',
    ];

    private static ?string $css = null;

    public static function path(): string
    {
        return dirname(__DIR__, 2).'/resources/css/toast-animations.css';
    }

    /**
     * Names accepted by `enter_animation` and `exit_animation`, including the
     * `none` sentinel that skips animation.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return ['none', ...self::NAMES];
    }

    public static function supports(?string $name): bool
    {
        return $name !== null && in_array($name, self::names(), true);
    }

    public static function css(): string
    {
        if (self::$css !== null) {
            return self::$css;
        }

        $path = self::path();
        $css = is_file($path) ? (string) file_get_contents($path) : '';
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);

        return self::$css = trim((string) preg_replace('/\n{2,}/', "\n", $css));
    }

    public static function styleTag(?string $framework = null): string
    {
        $theme = in_array($framework, ['bootstrap4', 'bootstrap5'], true)
            ? (string) file_get_contents(dirname(self::path()).'/toast-themes.css')
            : '';

        return '<style id="toast-animations">'.self::css().$theme.'</style>';
    }

    public static function flush(): void
    {
        self::$css = null;
    }
}
