<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Jeremykenedy\LaravelToast\Services\ToastManager;

class ToastServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/toast.php', 'toast');
        $this->app->singleton(ToastManager::class);
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerCommands();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerBladeDirectives();
        $this->registerLivewireComponents();
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/toast.php' => config_path('toast.php'),
            ], 'toast-config');

            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/toast'),
            ], 'toast-views');

            $this->publishes([
                __DIR__ . '/../../resources/lang' => $this->app->langPath('vendor/toast'),
            ], 'toast-lang');
        }
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Jeremykenedy\LaravelToast\Console\InstallCommand::class,
                \Jeremykenedy\LaravelToast\Console\SwitchCommand::class,
            ]);
        }
    }

    protected function registerViews(): void
    {
        $css = config('ui-kit.css_framework', 'tailwind');
        $bladePath = __DIR__ . '/../../resources/views/' . $css . '/blade';

        if (! is_dir($bladePath)) {
            $bladePath = __DIR__ . '/../../resources/views/tailwind/blade';
        }

        $this->loadViewsFrom($bladePath, 'toast');

        $livewirePath = __DIR__ . '/../../resources/views/livewire';
        if (is_dir($livewirePath)) {
            $this->loadViewsFrom($livewirePath, 'toast-livewire');
        }
    }

    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'toast');
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('toasts', function () {
            return "<?php echo view('toast::toasts')->render(); ?>";
        });
    }

    protected function registerLivewireComponents(): void
    {
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('toast-container', \Jeremykenedy\LaravelToast\Livewire\ToastContainer::class);
        }
    }
}
