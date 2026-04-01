<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Traits;

use Jeremykenedy\LaravelToast\Services\ToastManager;

trait HasToasts
{
    protected function toast(): ToastManager
    {
        return app(ToastManager::class);
    }

    protected function toastSuccess(string $message, ?string $title = null): static
    {
        $this->toast()->success($message, $title);
        return $this;
    }

    protected function toastError(string $message, ?string $title = null): static
    {
        $this->toast()->error($message, $title);
        return $this;
    }

    protected function toastWarning(string $message, ?string $title = null): static
    {
        $this->toast()->warning($message, $title);
        return $this;
    }

    protected function toastInfo(string $message, ?string $title = null): static
    {
        $this->toast()->info($message, $title);
        return $this;
    }
}
