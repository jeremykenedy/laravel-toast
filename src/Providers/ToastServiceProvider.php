<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Jeremykenedy\LaravelToast\Console\InstallCommand;
use Jeremykenedy\LaravelToast\Console\SwitchCommand;
use Jeremykenedy\LaravelToast\Console\UpdateCommand;
use Jeremykenedy\LaravelToast\Livewire\ToastContainer;
use Jeremykenedy\LaravelToast\Services\ToastManager;
use Jeremykenedy\LaravelToast\Support\ToastAnimations;
use Livewire\Livewire;

class ToastServiceProvider extends ServiceProvider
{
    /**
     * CSS frameworks that ship a view directory.
     *
     * @var list<string>
     */
    public const CSS_FRAMEWORKS = ['tailwind', 'bootstrap5', 'bootstrap4'];

    /**
     * Frontends the install and switch commands accept.
     *
     * @var list<string>
     */
    public const FRONTENDS = ['blade', 'livewire', 'vue', 'react', 'svelte'];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/toast.php', 'toast');
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

    /**
     * `toast.css_framework` is checked first so the package works standalone. It
     * defaults to null, leaving `ui-kit.css_framework` in control where that is
     * what the application uses.
     */
    public static function cssFramework(): string
    {
        $css = config('toast.css_framework') ?: config('ui-kit.css_framework');

        return in_array($css, self::CSS_FRAMEWORKS, true) ? $css : 'tailwind';
    }

    /**
     * Same precedence as the CSS framework.
     */
    public static function frontend(): string
    {
        $frontend = config('toast.frontend') ?: config('ui-kit.frontend');

        return in_array($frontend, self::FRONTENDS, true) ? $frontend : 'blade';
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/toast.php' => config_path('toast.php'),
            ], 'toast-config');

            $this->publishes([
                __DIR__.'/../../resources/views' => resource_path('views/vendor/toast'),
            ], 'toast-views');

            $this->publishes([
                __DIR__.'/../../resources/lang' => $this->app->langPath('vendor/toast'),
            ], 'toast-lang');

            $this->publishes([
                ToastAnimations::path() => resource_path('css/vendor/toast/toast-animations.css'),
                __DIR__.'/../../resources/css/toast-themes.css' => resource_path('css/vendor/toast/toast-themes.css'),
                __DIR__.'/../../resources/css/toast-components.css' => resource_path('css/vendor/toast/toast-components.css'),
            ], 'toast-css');
        }
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                UpdateCommand::class,
                SwitchCommand::class,
            ]);
        }
    }

    protected function registerViews(): void
    {
        $css = static::cssFramework();

        $bladePath = __DIR__.'/../../resources/views/'.$css.'/blade';

        if (!is_dir($bladePath)) {
            $bladePath = __DIR__.'/../../resources/views/tailwind/blade';
        }

        $this->loadViewsFrom($this->withPublishedOverride($bladePath, $css.'/blade'), 'toast');

        $livewirePath = __DIR__.'/../../resources/views/livewire';
        if (is_dir($livewirePath)) {
            $this->loadViewsFrom($this->livewireViewPaths($livewirePath, $css), 'toast-livewire');
        }
    }

    /**
     * Livewire lookup order, first match wins. The Tailwind markup stays at the
     * root of `views/livewire` so published overrides keep working; Bootstrap 5
     * and Bootstrap 4 live in their own subdirectories.
     *
     * @return list<string>
     */
    protected function livewireViewPaths(string $packagePath, string $css): array
    {
        $paths = [];

        $publishedVariant = resource_path('views/vendor/toast/livewire/'.$css);
        if (is_dir($publishedVariant)) {
            $paths[] = $publishedVariant;
        }

        // Ahead of the published root, which predates the per-framework views
        // and is Tailwind only, so an upgraded Bootstrap install would keep
        // rendering Tailwind markup.
        if (is_dir($packagePath.'/'.$css)) {
            $paths[] = $packagePath.'/'.$css;
        }

        $publishedRoot = resource_path('views/vendor/toast/livewire');
        if (is_dir($publishedRoot)) {
            $paths[] = $publishedRoot;
        }

        $paths[] = $packagePath;

        return $paths;
    }

    /**
     * Publishing copies the whole tree, so an override lands at
     * `views/vendor/toast/{framework}/blade`. Laravel only checks
     * `views/vendor/toast`, so register the nested path and let it win.
     *
     * @return list<string>
     */
    protected function withPublishedOverride(string $packagePath, string $relative): array
    {
        $paths = [];
        $published = resource_path('views/vendor/toast/'.$relative);

        if (is_dir($published)) {
            $paths[] = $published;
        }

        $paths[] = $packagePath;

        return $paths;
    }

    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'toast');
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('toasts', function () {
            return "<?php echo view('toast::toasts')->render(); ?>";
        });
    }

    protected function registerLivewireComponents(): void
    {
        // The class can be autoloadable while the provider is not loaded, and
        // registering a component in that state throws on livewire.finder.
        if (class_exists(Livewire::class) && $this->app->bound('livewire')) {
            Livewire::component('toast-container', ToastContainer::class);
        }
    }
}
