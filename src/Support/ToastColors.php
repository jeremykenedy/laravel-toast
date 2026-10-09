<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Support;

/**
 * Color overrides for the four toast types.
 *
 * Every renderer marks its markup with `data-toast-type` and `data-toast-part`,
 * so one generated stylesheet recolors Blade, Livewire, Vue, React and Svelte
 * in any CSS framework. Only parts that have a color set produce a rule;
 * everything else keeps the framework's own look.
 */
final class ToastColors
{
    /**
     * @var list<string>
     */
    public const TYPES = ['success', 'error', 'warning', 'info'];

    /**
     * @var list<string>
     */
    public const MODES = ['light', 'dark'];

    /**
     * Part name => CSS declarations produced for the part.
     *
     * @var array<string, array{selector: string, properties: list<string>}>
     */
    public const PARTS = [
        'background' => ['selector' => '', 'properties' => ['background-color']],
        'text'       => ['selector' => '', 'properties' => ['color']],
        'border'     => ['selector' => '', 'properties' => ['border-color']],
        'icon'       => ['selector' => ' [data-toast-part="icon"], %s [data-toast-part="icon"] svg', 'properties' => ['color']],
        'progress'   => ['selector' => ' [data-toast-part="bar"]', 'properties' => ['background-color']],
        'track'      => ['selector' => ' [data-toast-part="track"]', 'properties' => ['background-color']],
    ];

    /**
     * Bootstrap draws its close button in a fixed color, so it disappears on a
     * custom background. Setting a text color makes the button follow it.
     */
    private const CLOSE_ICON = '[data-laravel-toast]{--toast-close-icon:url("data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 16 16\'%3e%3cpath d=\'M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z\'/%3e%3c/svg%3e")}';

    /**
     * @var array<string, string>
     */
    private const CLOSE_RULES = [
        '.close'     => 'color:inherit!important',
        '.btn-close' => 'color:inherit!important;filter:none!important;background-image:none!important;background-color:currentColor!important;-webkit-mask:var(--toast-close-icon) center/1em auto no-repeat;mask:var(--toast-close-icon) center/1em auto no-repeat',
    ];

    private const DARK_SCOPE = ':where(.dark, [data-bs-theme="dark"])';

    /**
     * @return list<string>
     */
    public static function parts(): array
    {
        return array_keys(self::PARTS);
    }

    /**
     * Keep only known types, modes and parts, and only valid hex colors.
     * Output is written straight into a stylesheet, so nothing else passes.
     *
     * @param array<string, mixed>|null $colors
     *
     * @return array<string, array<string, array<string, string>>>
     */
    public static function normalize(?array $colors): array
    {
        $clean = [];

        foreach (self::TYPES as $type) {
            foreach (self::MODES as $mode) {
                foreach (self::parts() as $part) {
                    $value = self::hex($colors[$type][$mode][$part] ?? null);

                    if ($value !== null) {
                        $clean[$type][$mode][$part] = $value;
                    }
                }
            }
        }

        return $clean;
    }

    /**
     * @param array<string, array<string, array<string, string>>> $normalized
     */
    private static function sets(array $normalized, string $part): bool
    {
        foreach ($normalized as $modes) {
            foreach ($modes as $parts) {
                if (isset($parts[$part])) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function hex(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
            return null;
        }

        $value = strtolower($value);

        return strlen($value) === 4
            ? '#'.$value[1].$value[1].$value[2].$value[2].$value[3].$value[3]
            : $value;
    }

    /**
     * Scope prefixes per mode. The settings preview swaps these for its own
     * light and dark containers so both render regardless of the page theme.
     *
     * @param array<string, mixed>|null            $colors
     * @param array{light?: string, dark?: string} $scopes
     */
    public static function css(?array $colors, array $scopes = []): string
    {
        $scopes += ['light' => '', 'dark' => self::DARK_SCOPE.' '];

        $rules = [];
        $normalized = self::normalize($colors);

        if (self::sets($normalized, 'text')) {
            $rules[] = self::CLOSE_ICON;
        }

        foreach ($normalized as $type => $modes) {
            foreach ($modes as $mode => $parts) {
                $base = $scopes[$mode].'[data-laravel-toast][data-toast-type="'.$type.'"]';

                foreach ($parts as $part => $value) {
                    $definition = self::PARTS[$part];
                    $selector = str_contains($definition['selector'], '%s')
                        ? $base.sprintf($definition['selector'], $base)
                        : $base.$definition['selector'];
                    $declarations = implode('', array_map(fn (string $property) => $property.':'.$value.'!important;', $definition['properties']));

                    $rules[] = $selector.'{'.rtrim($declarations, ';').'}';

                    if ($part === 'text') {
                        foreach (self::CLOSE_RULES as $closeSelector => $closeDeclarations) {
                            $rules[] = $base.' '.$closeSelector.'{'.$closeDeclarations.'}';
                        }
                    }
                }
            }
        }

        return implode("\n", $rules);
    }

    /**
     * @param array<string, mixed>|null $colors
     */
    public static function styleTag(?array $colors): string
    {
        $css = self::css($colors);

        return $css === '' ? '' : '<style id="toast-colors">'.$css.'</style>';
    }
}
