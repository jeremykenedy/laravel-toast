<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Facades;

use Illuminate\Support\Facades\Facade;
use Jeremykenedy\LaravelToast\Services\ToastManager;

/**
 * @method static \Jeremykenedy\LaravelToast\Services\ToastManager success(string $message, ?string $title = null, ?int $duration = null)
 * @method static \Jeremykenedy\LaravelToast\Services\ToastManager error(string $message, ?string $title = null, ?int $duration = null)
 * @method static \Jeremykenedy\LaravelToast\Services\ToastManager warning(string $message, ?string $title = null, ?int $duration = null)
 * @method static \Jeremykenedy\LaravelToast\Services\ToastManager info(string $message, ?string $title = null, ?int $duration = null)
 * @method static array get()
 * @method static \Jeremykenedy\LaravelToast\Services\ToastManager clear()
 *
 * @see \Jeremykenedy\LaravelToast\Services\ToastManager
 */
class Toast extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ToastManager::class;
    }
}
