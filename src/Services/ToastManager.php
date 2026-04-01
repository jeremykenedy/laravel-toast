<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Services;

use Illuminate\Support\Facades\Session;

class ToastManager
{
    protected const VALID_POSITIONS = [
        'top-right',
        'top-left',
        'top-center',
        'bottom-right',
        'bottom-left',
        'bottom-center',
    ];

    protected const VALID_TYPES = [
        'success',
        'error',
        'warning',
        'info',
    ];

    /**
     * @param  array{auto_dismiss?: bool, show_icon?: bool, show_progress?: bool, custom_icon?: string|null}  $options
     */
    public function success(string $message, ?string $title = null, ?int $duration = null, array $options = []): static
    {
        return $this->add('success', $message, $title, $duration, $options);
    }

    /**
     * @param  array{auto_dismiss?: bool, show_icon?: bool, show_progress?: bool, custom_icon?: string|null}  $options
     */
    public function error(string $message, ?string $title = null, ?int $duration = null, array $options = []): static
    {
        return $this->add('error', $message, $title, $duration, $options);
    }

    /**
     * @param  array{auto_dismiss?: bool, show_icon?: bool, show_progress?: bool, custom_icon?: string|null}  $options
     */
    public function warning(string $message, ?string $title = null, ?int $duration = null, array $options = []): static
    {
        return $this->add('warning', $message, $title, $duration, $options);
    }

    /**
     * @param  array{auto_dismiss?: bool, show_icon?: bool, show_progress?: bool, custom_icon?: string|null}  $options
     */
    public function info(string $message, ?string $title = null, ?int $duration = null, array $options = []): static
    {
        return $this->add('info', $message, $title, $duration, $options);
    }

    /**
     * @param  array{
     *     position?: string,
     *     auto_dismiss?: bool,
     *     show_icon?: bool,
     *     show_progress?: bool,
     *     custom_icon?: string|null,
     *     progress_direction?: string,
     *     pause_on_hover?: bool,
     *     opacity?: float,
     *     max_visible?: int,
     * }  $options  All props are optional; defaults come from config/toast.php.
     */
    public function add(string $type, string $message, ?string $title = null, ?int $duration = null, array $options = []): static
    {
        $type = in_array($type, self::VALID_TYPES) ? $type : 'info';

        $key = $this->sessionKey();
        $toasts = Session::get($key, []);

        $toasts[] = [
            'id' => uniqid('toast_'),
            'type' => $type,
            'message' => $message,
            'title' => $title,
            'duration' => $duration ?? (int) config('toast.duration', 5000),
            'position' => $options['position'] ?? null,
            'auto_dismiss' => $options['auto_dismiss'] ?? (bool) config('toast.auto_dismiss', true),
            'pause_on_hover' => $options['pause_on_hover'] ?? (bool) config('toast.pause_on_hover', true),
            'show_icon' => $options['show_icon'] ?? (bool) config('toast.show_icons', true),
            'custom_icon' => $options['custom_icon'] ?? null,
            'show_progress' => $options['show_progress'] ?? (bool) config('toast.show_progress', true),
            'progress_direction' => $options['progress_direction'] ?? config('toast.progress_direction', 'rtl'),
            'opacity' => $options['opacity'] ?? (float) config('toast.opacity', 1),
            'show_border' => $options['show_border'] ?? (bool) config('toast.show_border', true),
            'show_close' => $options['show_close'] ?? (bool) config('toast.show_close', true),
            'progress_position' => $options['progress_position'] ?? config('toast.progress_position', 'top'),
            'dir' => $options['dir'] ?? config('toast.dir', 'ltr'),
            'enter_animation' => $options['enter_animation'] ?? config('toast.enter_animation', 'none'),
            'enter_duration' => $options['enter_duration'] ?? (float) config('toast.enter_duration', 0.5),
            'exit_animation' => $options['exit_animation'] ?? config('toast.exit_animation', 'none'),
            'exit_duration' => $options['exit_duration'] ?? (float) config('toast.exit_duration', 0.5),
            'max_visible' => $options['max_visible'] ?? (int) config('toast.max_visible', 5),
            'timestamp' => now()->toISOString(),
        ];

        $max = (int) config('toast.max_visible', 5);
        if (count($toasts) > $max) {
            $toasts = array_slice($toasts, -$max);
        }

        Session::flash($key, $toasts);

        return $this;
    }

    public function get(): array
    {
        return Session::get($this->sessionKey(), []);
    }

    public function clear(): static
    {
        Session::forget($this->sessionKey());

        return $this;
    }

    public function position(): string
    {
        $position = config('toast.position', 'top-right');

        return in_array($position, self::VALID_POSITIONS) ? $position : 'top-right';
    }

    public function convertFlashMessages(): void
    {
        if (session('success')) {
            $this->success(session('success'));
        }
        if (session('error')) {
            $this->error(session('error'));
        }
        if (session('warning')) {
            $this->warning(session('warning'));
        }
        if (session('info') && is_string(session('info'))) {
            $this->info(session('info'));
        }
        if (session('status') && is_string(session('status'))) {
            $this->info(session('status'));
        }
    }

    public function validPositions(): array
    {
        return self::VALID_POSITIONS;
    }

    public function validTypes(): array
    {
        return self::VALID_TYPES;
    }

    protected function sessionKey(): string
    {
        return config('toast.session_key', 'toast_notifications');
    }
}
