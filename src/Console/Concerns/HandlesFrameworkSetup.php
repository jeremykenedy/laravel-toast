<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Console\Concerns;

use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;

trait HandlesFrameworkSetup
{
    protected function getCssOption(): string
    {
        return $this->option('css') ?? ToastServiceProvider::cssFramework();
    }

    protected function getFrontendOption(): string
    {
        return $this->option('frontend') ?? ToastServiceProvider::frontend();
    }

    /**
     * @return list<string>
     */
    protected function validCssFrameworks(): array
    {
        return ToastServiceProvider::CSS_FRAMEWORKS;
    }

    /**
     * @return list<string>
     */
    protected function validFrontends(): array
    {
        return ToastServiceProvider::FRONTENDS;
    }

    protected function updateEnvValue(string $key, string $value): void
    {
        if ($this->laravel->runningUnitTests()) {
            return;
        }

        $path = $this->laravel->environmentFilePath();

        if (!file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return;
        }

        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (!preg_match($pattern, $content)) {
            file_put_contents($path, rtrim($content, "\r\n")."\n{$key}={$value}\n");

            return;
        }

        file_put_contents($path, preg_replace($pattern, "{$key}={$value}", $content));
    }

    protected function setCssFramework(string $css): void
    {
        $this->assignFramework('css_framework', 'UI_KIT_CSS', 'TOAST_CSS', $css);
    }

    protected function setFrontendFramework(string $frontend): void
    {
        $this->assignFramework('frontend', 'UI_KIT_FRONTEND', 'TOAST_FRONTEND', $frontend);
    }

    /**
     * Follows ui-kit unless toast carries its own override, so the command
     * changes the setting that actually controls toast.
     */
    private function assignFramework(string $configKey, string $uiKitEnv, string $toastEnv, string $value): void
    {
        $followsKit = $this->uiKitIsInstalled() && !config("toast.{$configKey}");

        $this->updateEnvValue($followsKit ? $uiKitEnv : $toastEnv, $value);
        config([($followsKit ? "ui-kit.{$configKey}" : "toast.{$configKey}") => $value]);

        $this->clearCaches();
    }

    protected function uiKitIsInstalled(): bool
    {
        return config('ui-kit') !== null;
    }

    protected function clearCaches(): void
    {
        $this->call('config:clear');
        $this->call('view:clear');
    }
}
