<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Livewire;

use Jeremykenedy\LaravelToast\Services\ToastManager;
use Livewire\Attributes\On;
use Livewire\Component;

class ToastContainer extends Component
{
    public array $toasts = [];

    public function mount(): void
    {
        $manager = app(ToastManager::class);

        if (config('toast.convert_flash', true)) {
            $manager->convertFlashMessages();
        }

        $this->toasts = $manager->get();
    }

    #[On('toast')]
    public function addToast(
        string $message,
        string $type = 'info',
        ?string $title = null,
        ?int $duration = null,
        ?array $options = null,
    ): void {
        $opts = $options ?? [];
        $stack = config('toast.stack', true);

        $toast = [
            'id'                 => uniqid('toast_'),
            'type'               => in_array($type, ['success', 'error', 'warning', 'info']) ? $type : 'info',
            'message'            => $message,
            'title'              => $title,
            'duration'           => $duration ?? (int) config('toast.duration', 5000),
            'position'           => $opts['position'] ?? null,
            'dir'                => $opts['dir'] ?? config('toast.dir', 'ltr'),
            'auto_dismiss'       => $opts['auto_dismiss'] ?? (bool) config('toast.auto_dismiss', true),
            'pause_on_hover'     => $opts['pause_on_hover'] ?? (bool) config('toast.pause_on_hover', true),
            'show_icon'          => $opts['show_icon'] ?? (bool) config('toast.show_icons', true),
            'custom_icon'        => $opts['custom_icon'] ?? null,
            'show_border'        => $opts['show_border'] ?? (bool) config('toast.show_border', true),
            'show_close'         => $opts['show_close'] ?? (bool) config('toast.show_close', true),
            'show_progress'      => $opts['show_progress'] ?? (bool) config('toast.show_progress', true),
            'progress_direction' => $opts['progress_direction'] ?? config('toast.progress_direction', 'rtl'),
            'progress_position'  => $opts['progress_position'] ?? config('toast.progress_position', 'top'),
            'opacity'            => $opts['opacity'] ?? (float) config('toast.opacity', 1),
            'enter_animation'    => $opts['enter_animation'] ?? config('toast.enter_animation', 'none'),
            'enter_duration'     => $opts['enter_duration'] ?? (float) config('toast.enter_duration', 0.5),
            'exit_animation'     => $opts['exit_animation'] ?? config('toast.exit_animation', 'none'),
            'exit_duration'      => $opts['exit_duration'] ?? (float) config('toast.exit_duration', 0.5),
            'max_visible'        => $opts['max_visible'] ?? (int) config('toast.max_visible', 5),
        ];

        if (!$stack) {
            $this->toasts = [$toast];
        } else {
            $this->toasts[] = $toast;
        }
    }

    #[On('toast-success')]
    public function addSuccess(string $message, ?string $title = null, ?array $options = null): void
    {
        $this->addToast($message, 'success', $title, null, $options);
    }

    #[On('toast-error')]
    public function addErrorToast(string $message, ?string $title = null, ?array $options = null): void
    {
        $this->addToast($message, 'error', $title, null, $options);
    }

    #[On('toast-warning')]
    public function addWarning(string $message, ?string $title = null, ?array $options = null): void
    {
        $this->addToast($message, 'warning', $title, null, $options);
    }

    #[On('toast-info')]
    public function addInfo(string $message, ?string $title = null, ?array $options = null): void
    {
        $this->addToast($message, 'info', $title, null, $options);
    }

    public function dismiss(string $id): void
    {
        $this->toasts = array_values(array_filter(
            $this->toasts,
            fn (array $t) => $t['id'] !== $id
        ));
    }

    public function render()
    {
        return view('toast-livewire::toast-container');
    }
}
