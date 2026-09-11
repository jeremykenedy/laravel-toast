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
        if (app()->runningUnitTests()) {
            return;
        }

        $path = base_path('.env');

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
        // UI_KIT_CSS is still written so laravel-ui-kit applications, where one
        // switch moves every package together, keep their existing behavior.
        $this->updateEnvValue('TOAST_CSS', $css);
        $this->updateEnvValue('UI_KIT_CSS', $css);

        config(['toast.css_framework' => $css]);

        $this->clearCaches();
    }

    protected function setFrontendFramework(string $frontend): void
    {
        $this->updateEnvValue('TOAST_FRONTEND', $frontend);
        $this->updateEnvValue('UI_KIT_FRONTEND', $frontend);

        config(['toast.frontend' => $frontend]);

        $this->clearCaches();
    }

    protected function clearCaches(): void
    {
        $this->call('config:clear');
        $this->call('view:clear');
    }
}
