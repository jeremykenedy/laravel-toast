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
        $toast = app(ToastManager::class)->build($type, $message, $title, $duration, $options ?? []);

        if (!config('toast.stack', true)) {
            $this->toasts = [$toast];

            return;
        }

        $this->toasts[] = $toast;

        // Mirrors ToastManager::add() so a dispatch loop cannot grow unbounded.
        $max = (int) $toast['max_visible'];
        if ($max > 0 && count($this->toasts) > $max) {
            $this->toasts = array_slice($this->toasts, -$max);
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
