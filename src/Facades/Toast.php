<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Facades;

use Illuminate\Support\Facades\Facade;
use Jeremykenedy\LaravelToast\Services\ToastManager;

/**
 * @method static \Jeremykenedy\LaravelToast\Services\ToastManager success(string $message, ?string $title = null, ?int $duration = null, array $options = [])
 * @method static \Jeremykenedy\LaravelToast\Services\ToastManager error(string $message, ?string $title = null, ?int $duration = null, array $options = [])
 * @method static \Jeremykenedy\LaravelToast\Services\ToastManager warning(string $message, ?string $title = null, ?int $duration = null, array $options = [])
 * @method static \Jeremykenedy\LaravelToast\Services\ToastManager info(string $message, ?string $title = null, ?int $duration = null, array $options = [])
 * @method static \Jeremykenedy\LaravelToast\Services\ToastManager add(string $type, string $message, ?string $title = null, ?int $duration = null, array $options = [])
 * @method static array                                            build(string $type, string $message, ?string $title = null, ?int $duration = null, array $options = [])
 * @method static \Jeremykenedy\LaravelToast\Services\ToastManager broadcast(string|int $userId, string $message, string $type = 'info', ?string $title = null, ?int $duration = null, array $options = [])
 * @method static array                                            get()
 * @method static \Jeremykenedy\LaravelToast\Services\ToastManager clear()
 * @method static string                                           position()
 * @method static void                                             convertFlashMessages()
 * @method static array                                            validPositions()
 * @method static array                                            validTypes()
 *
 * @see ToastManager
 */
class Toast extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ToastManager::class;
    }
}
