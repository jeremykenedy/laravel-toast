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

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "{$key}={$value}", $content);
        } else {
            $content = rtrim($content, "\r\n")."\n{$key}={$value}\n";
        }

        file_put_contents($path, $content);
    }

    protected function setCssFramework(string $css): void
    {
        if ($this->uiKitIsInstalled() && !config('toast.css_framework')) {
            $this->updateEnvValue('UI_KIT_CSS', $css);
            config(['ui-kit.css_framework' => $css]);
        } else {
            $this->updateEnvValue('TOAST_CSS', $css);
            config(['toast.css_framework' => $css]);
        }

        $this->clearCaches();
    }

    protected function setFrontendFramework(string $frontend): void
    {
        if ($this->uiKitIsInstalled() && !config('toast.frontend')) {
            $this->updateEnvValue('UI_KIT_FRONTEND', $frontend);
            config(['ui-kit.frontend' => $frontend]);
        } else {
            $this->updateEnvValue('TOAST_FRONTEND', $frontend);
            config(['toast.frontend' => $frontend]);
        }

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
