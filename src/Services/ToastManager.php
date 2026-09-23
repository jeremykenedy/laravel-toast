<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Event;
use Jeremykenedy\LaravelToast\Events\ToastBroadcast;
use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;

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
     * @param array{auto_dismiss?: bool, show_icon?: bool, show_progress?: bool, custom_icon?: string|null} $options
     */
    public function success(string $message, ?string $title = null, ?int $duration = null, array $options = []): static
    {
        return $this->add('success', $message, $title, $duration, $options);
    }

    /**
     * @param array{auto_dismiss?: bool, show_icon?: bool, show_progress?: bool, custom_icon?: string|null} $options
     */
    public function error(string $message, ?string $title = null, ?int $duration = null, array $options = []): static
    {
        return $this->add('error', $message, $title, $duration, $options);
    }

    /**
     * @param array{auto_dismiss?: bool, show_icon?: bool, show_progress?: bool, custom_icon?: string|null} $options
     */
    public function warning(string $message, ?string $title = null, ?int $duration = null, array $options = []): static
    {
        return $this->add('warning', $message, $title, $duration, $options);
    }

    /**
     * @param array{auto_dismiss?: bool, show_icon?: bool, show_progress?: bool, custom_icon?: string|null} $options
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
        $key = $this->sessionKey();
        $toasts = Session::get($key, []);

        $toast = $this->build($type, $message, $title, $duration, $options);
        Session::flash($key, $this->append($toasts, $toast));

        return $this;
    }

    /**
     * Build a toast payload without touching the session. The Livewire
     * component builds through here too, so defaults resolve in one place.
     *
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
     *
     * @return array<string, mixed>
     */
    public function build(string $type, string $message, ?string $title = null, ?int $duration = null, array $options = []): array
    {
        $type = in_array($type, self::VALID_TYPES) ? $type : 'info';

        return [
            'id'                 => uniqid('toast_'),
            'type'               => $type,
            'message'            => $message,
            'title'              => $title,
            'duration'           => $duration ?? (int) config('toast.duration', 5000),
            'position'           => $options['position'] ?? $this->position(),
            'stack'              => $options['stack'] ?? (bool) config('toast.stack', true),
            'css_framework'      => ToastServiceProvider::cssFramework(),
            'auto_dismiss'       => $options['auto_dismiss'] ?? (bool) config('toast.auto_dismiss', true),
            'pause_on_hover'     => $options['pause_on_hover'] ?? (bool) config('toast.pause_on_hover', true),
            'show_icon'          => $options['show_icon'] ?? (bool) config('toast.show_icons', true),
            'custom_icon'        => $options['custom_icon'] ?? null,
            'show_progress'      => $options['show_progress'] ?? (bool) config('toast.show_progress', true),
            'progress_direction' => $options['progress_direction'] ?? config('toast.progress_direction', 'rtl'),
            'opacity'            => $options['opacity'] ?? (float) config('toast.opacity', 1),
            'show_border'        => $options['show_border'] ?? (bool) config('toast.show_border', true),
            'show_close'         => $options['show_close'] ?? (bool) config('toast.show_close', true),
            'progress_position'  => $options['progress_position'] ?? config('toast.progress_position', 'top'),
            'dir'                => $options['dir'] ?? config('toast.dir', 'ltr'),
            'enter_animation'    => $options['enter_animation'] ?? config('toast.enter_animation', 'none'),
            'enter_duration'     => $options['enter_duration'] ?? (float) config('toast.enter_duration', 0.5),
            'exit_animation'     => $options['exit_animation'] ?? config('toast.exit_animation', 'none'),
            'exit_duration'      => $options['exit_duration'] ?? (float) config('toast.exit_duration', 0.5),
            'max_visible'        => $options['max_visible'] ?? (int) config('toast.max_visible', 5),
            'timestamp'          => now()->toISOString(),
        ];
    }

    public function get(): array
    {
        return array_reduce(Session::get($this->sessionKey(), []), $this->append(...), []);
    }

    public function append(array $toasts, array $toast): array
    {
        $toasts = ($toast['stack'] ?? config('toast.stack', true)) ? [...$toasts, $toast] : [$toast];
        $max = (int) ($toast['max_visible'] ?? config('toast.max_visible', 5));

        return $max > 0 ? array_slice($toasts, -$max) : $toasts;
    }

    public function broadcast(string|int $userId, string $message, string $type = 'info', ?string $title = null, ?int $duration = null, array $options = []): static
    {
        if (config('toast.broadcast.enabled', false)) {
            Event::dispatch(new ToastBroadcast(
                str_replace('{userId}', (string) $userId, config('toast.broadcast.channel', 'toast.{userId}')),
                $this->build($type, $message, $title, $duration, $options),
            ));
        }

        return $this;
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
        $toasts = $this->get();
        $converted = false;

        foreach (['success', 'error', 'warning', 'info', 'status'] as $key) {
            $message = Session::get($key);

            if (!is_string($message) || $message === '') {
                continue;
            }

            Session::forget($key);
            $toasts = $this->append($toasts, $this->build($key === 'status' ? 'info' : $key, $message));
            $converted = true;
        }

        if ($converted) {
            Session::now($this->sessionKey(), $toasts);
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
