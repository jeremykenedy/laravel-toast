<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Livewire;

use Illuminate\Support\Facades\Auth;
use Jeremykenedy\LaravelToast\Services\ToastManager;
use Livewire\Attributes\On;
use Livewire\Component;

class ToastContainer extends Component
{
    public array $toasts = [];

    protected ToastManager $manager;

    public function boot(ToastManager $manager): void
    {
        $this->manager = $manager;
    }

    public function mount(): void
    {
        $manager = $this->manager;

        if (config('toast.convert_flash', true)) {
            $manager->convertFlashMessages();
        }

        $this->toasts = $manager->get();
    }

    public function getListeners(): array
    {
        if (!config('toast.broadcast.enabled', false) || ($userId = Auth::id()) === null) {
            return [];
        }

        $channel = str_replace('{userId}', (string) $userId, config('toast.broadcast.channel', 'toast.{userId}'));

        return ['echo-private:'.$channel.',.toast' => 'receiveBroadcast'];
    }

    public function receiveBroadcast(array $event): void
    {
        $payload = $event['toast'] ?? [];

        if (!is_string($payload['id'] ?? null) || !is_string($payload['message'] ?? null)
            || in_array($payload['id'], array_column($this->toasts, 'id'), true)) {
            return;
        }

        $toast = $this->manager->build(
            $payload['type'] ?? 'info',
            $payload['message'],
            $payload['title'] ?? null,
            $payload['duration'] ?? null,
            $payload,
        );
        $toast['id'] = $payload['id'];
        $this->toasts = $this->manager->append($this->toasts, $toast);
    }

    #[On('toast')]
    public function addToast(
        string $message,
        string $type = 'info',
        ?string $title = null,
        ?int $duration = null,
        ?array $options = null,
    ): void {
        $toast = $this->manager->build($type, $message, $title, $duration, $options ?? []);
        $this->toasts = $this->manager->append($this->toasts, $toast);
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
