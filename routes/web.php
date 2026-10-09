<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Jeremykenedy\LaravelToast\Http\Controllers\ToastSettingsController;
use Jeremykenedy\LaravelToast\Http\Middleware\AuthorizeToastSettings;

Route::middleware([...(array) config('toast.settings.middleware', ['web', 'auth']), AuthorizeToastSettings::class])
    ->prefix(config('toast.settings.prefix', 'toast/settings'))
    ->name(config('toast.settings.name', 'toast.settings.'))
    ->group(function () {
        if (config('toast.settings.page', false)) {
            Route::get('/', [ToastSettingsController::class, 'index'])->name('index');
        }

        Route::get('/data', [ToastSettingsController::class, 'show'])->name('show');
        Route::put('/', [ToastSettingsController::class, 'update'])->name('update');
        Route::delete('/', [ToastSettingsController::class, 'destroy'])->name('destroy');
    });
