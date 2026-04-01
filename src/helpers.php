<?php

declare(strict_types=1);

use Jeremykenedy\LaravelToast\Services\ToastManager;

if (!function_exists('toast')) {
    function toast(?string $message = null, string $type = 'success', ?string $title = null, ?int $duration = null, array $options = []): ToastManager
    {
        $manager = app(ToastManager::class);

        if ($message !== null) {
            $manager->add($type, $message, $title, $duration, $options);
        }

        return $manager;
    }
}
