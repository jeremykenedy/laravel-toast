<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Jeremykenedy\LaravelToast\Models\ToastSetting;
use Throwable;

/**
 * Saved toast options. When the settings feature is enabled, saved values are
 * merged over config/env so every renderer reads one source of truth.
 */
class ToastSettings
{
    private const CACHE_KEY = 'laravel-toast.settings';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function fields(): array
    {
        return [
            'position'           => ['type' => 'select', 'default' => 'top-right', 'options' => ['top-right', 'top-left', 'top-center', 'bottom-right', 'bottom-left', 'bottom-center']],
            'dir'                => ['type' => 'select', 'default' => 'ltr', 'options' => ['ltr', 'rtl']],
            'duration'           => ['type' => 'number', 'default' => 5000, 'min' => 0, 'max' => 3600000, 'step' => 100],
            'max_visible'        => ['type' => 'number', 'default' => 5, 'min' => 0, 'max' => 100, 'step' => 1],
            'opacity'            => ['type' => 'number', 'default' => 1, 'min' => 0, 'max' => 1, 'step' => 0.05],
            'enter_animation'    => ['type' => 'select', 'default' => 'none', 'options' => ToastAnimations::names()],
            'enter_duration'     => ['type' => 'number', 'default' => 0.5, 'min' => 0, 'max' => 5, 'step' => 0.1],
            'exit_animation'     => ['type' => 'select', 'default' => 'none', 'options' => ToastAnimations::names()],
            'exit_duration'      => ['type' => 'number', 'default' => 0.5, 'min' => 0, 'max' => 5, 'step' => 0.1],
            'progress_direction' => ['type' => 'select', 'default' => 'rtl', 'options' => ['rtl', 'ltr']],
            'progress_position'  => ['type' => 'select', 'default' => 'top', 'options' => ['top', 'bottom']],
            'auto_dismiss'       => ['type' => 'checkbox', 'default' => true],
            'pause_on_hover'     => ['type' => 'checkbox', 'default' => true],
            'stack'              => ['type' => 'checkbox', 'default' => true],
            'show_icons'         => ['type' => 'checkbox', 'default' => true],
            'show_border'        => ['type' => 'checkbox', 'default' => true],
            'show_close'         => ['type' => 'checkbox', 'default' => true],
            'show_progress'      => ['type' => 'checkbox', 'default' => true],
            'convert_flash'      => ['type' => 'checkbox', 'default' => true],
        ];
    }

    /**
     * Everything a settings UI needs to render and submit.
     *
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        $current = static::current();
        $defaults = static::defaults();

        return [
            'options'  => $current['options'],
            'colors'   => $current['colors'],
            'defaults' => $defaults,
            'fields'   => self::fields(),
            'types'    => ToastColors::TYPES,
            'parts'    => ToastColors::parts(),
            'ready'    => static::ready(),
            'urls'     => [
                'update'  => route(config('toast.settings.name', 'toast.settings.').'update'),
                'destroy' => route(config('toast.settings.name', 'toast.settings.').'destroy'),
                'show'    => route(config('toast.settings.name', 'toast.settings.').'show'),
            ],
        ];
    }

    /**
     * The color generator and panel script for the Blade settings views, read
     * from the same files the Vue, React and Svelte components import.
     */
    public static function script(): string
    {
        $path = dirname(__DIR__, 2).'/resources/js/';
        $generator = (string) preg_replace('/^export\s+/m', '', (string) file_get_contents($path.'toast-colors.js'));

        return $generator."\n;\n".file_get_contents($path.'toast-settings-panel.js');
    }

    public static function enabled(): bool
    {
        return (bool) config('toast.settings.enabled', false);
    }

    /**
     * An undefined gate denies, so enabling the feature never opens the page
     * until the application defines who may use it.
     */
    public static function canManage(mixed $user = null): bool
    {
        $gate = (string) config('toast.settings.gate', 'manage-toast-settings');

        return static::enabled() && $gate !== '' && Gate::forUser($user ?? auth()->user())->allows($gate);
    }

    public static function ready(): bool
    {
        try {
            return Schema::connection(config('toast.settings.connection'))->hasTable((string) config('toast.settings.table', 'toast_settings'));
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Current values: saved first, then config, then the field default.
     *
     * @return array{options: array<string, mixed>, colors: array<string, mixed>}
     */
    public static function current(): array
    {
        $options = [];

        foreach (self::fields() as $key => $field) {
            $options[$key] = config('toast.'.$key, $field['default']);
        }

        return ['options' => $options, 'colors' => ToastColors::normalize(config('toast.colors'))];
    }

    /**
     * Config/env values with no saved row applied, used by the reset buttons.
     *
     * @return array{options: array<string, mixed>, colors: array<string, mixed>}
     */
    public static function defaults(): array
    {
        $defaults = require dirname(__DIR__, 2).'/config/toast.php';
        $options = [];

        foreach (self::fields() as $key => $field) {
            $options[$key] = $defaults[$key] ?? $field['default'];
        }

        return ['options' => $options, 'colors' => []];
    }

    /**
     * @return array<string, mixed>
     */
    public static function saved(): array
    {
        if (!static::enabled()) {
            return [];
        }

        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => ToastSetting::query()->where('key', ToastSetting::GLOBAL_KEY)->value('value') ?? []);
        } catch (Throwable) {
            return [];
        }
    }

    public static function apply(): void
    {
        $saved = static::saved();

        foreach (array_keys(self::fields()) as $key) {
            if (array_key_exists($key, $saved['options'] ?? [])) {
                config(['toast.'.$key => $saved['options'][$key]]);
            }
        }

        if (array_key_exists('colors', $saved)) {
            config(['toast.colors' => $saved['colors']]);
        }
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array{options: array<string, mixed>, colors: array<string, mixed>}
     */
    public static function save(array $input): array
    {
        $current = static::current();
        $options = $current['options'];

        foreach (self::fields() as $key => $field) {
            if (array_key_exists($key, $input['options'] ?? [])) {
                $options[$key] = self::cast($field, $key, $input['options'][$key]);
            }
        }

        $payload = [
            'options' => $options,
            'colors'  => array_key_exists('colors', $input) ? ToastColors::normalize($input['colors']) : $current['colors'],
        ];

        ToastSetting::query()->updateOrCreate(['key' => ToastSetting::GLOBAL_KEY], ['value' => $payload]);
        Cache::forget(self::CACHE_KEY);
        static::apply();

        return $payload;
    }

    public static function reset(): void
    {
        ToastSetting::query()->where('key', ToastSetting::GLOBAL_KEY)->delete();
        Cache::forget(self::CACHE_KEY);

        foreach (static::defaults()['options'] as $key => $value) {
            config(['toast.'.$key => $value]);
        }

        config(['toast.colors' => []]);
    }

    /**
     * @param array<string, mixed> $field
     */
    private static function cast(array $field, string $key, mixed $value): mixed
    {
        return match ($field['type']) {
            'checkbox' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number'   => $field['step'] === 1 || $key === 'duration' ? (int) $value : (float) $value,
            default    => $value,
        };
    }

    /**
     * Rules shared by every endpoint that accepts settings.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $rules = [
            'options' => ['sometimes', 'array:'.implode(',', array_keys(self::fields()))],
            'colors'  => ['sometimes', 'array:'.implode(',', ToastColors::TYPES)],
        ];

        foreach (self::fields() as $key => $field) {
            $rules['options.'.$key] = match ($field['type']) {
                'select'   => ['sometimes', Rule::in($field['options'])],
                'checkbox' => ['sometimes', 'boolean'],
                default    => ['sometimes', $field['step'] === 1 || $key === 'duration' ? 'integer' : 'numeric', 'min:'.$field['min'], 'max:'.$field['max']],
            };
        }

        foreach (ToastColors::TYPES as $type) {
            $rules['colors.'.$type] = ['sometimes', 'array:'.implode(',', ToastColors::MODES)];

            foreach (ToastColors::MODES as $mode) {
                $rules['colors.'.$type.'.'.$mode] = ['sometimes', 'array:'.implode(',', ToastColors::parts())];

                foreach (ToastColors::parts() as $part) {
                    $rules['colors.'.$type.'.'.$mode.'.'.$part] = ['nullable', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'];
                }
            }
        }

        return $rules;
    }
}
