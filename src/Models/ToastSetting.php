<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Models;

use Illuminate\Database\Eloquent\Model;

class ToastSetting extends Model
{
    public const GLOBAL_KEY = 'global';

    protected $fillable = ['key', 'value'];

    public function getTable(): string
    {
        return (string) config('toast.settings.table', 'toast_settings');
    }

    public function getConnectionName(): ?string
    {
        return config('toast.settings.connection');
    }

    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
