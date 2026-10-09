<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Console;

use Illuminate\Console\Command;
use Jeremykenedy\LaravelToast\Console\Concerns\HandlesFrameworkSetup;
use Jeremykenedy\LaravelToast\Console\Concerns\HandlesSettingsSetup;
use Jeremykenedy\LaravelToast\Console\Concerns\HasInstallPrompts;

use function Laravel\Prompts\info;

class UpdateCommand extends Command
{
    use HandlesFrameworkSetup;
    use HandlesSettingsSetup;
    use HasInstallPrompts;

    protected $signature = 'toast:update
        {--css= : CSS framework (tailwind, bootstrap5, bootstrap4)}
        {--frontend= : Frontend framework (blade, livewire, vue, react, svelte)}
        {--settings : Enable the notification settings (publishes the migration)}
        {--settings-page : Also publish a full settings page}
        {--settings-layout= : Blade layout the settings page extends, for example layouts.app}
        {--settings-section= : Section name that layout yields (default: content)}
        {--settings-middleware= : Comma separated route middleware (default: web,auth)}
        {--settings-gate= : Gate that authorizes changes (default: manage-toast-settings)}';

    protected $description = 'Update the CSS and/or frontend framework for Laravel Toast';

    public function handle(): int
    {
        $this->renderBanner('TOAST');

        if (!$this->isInstalled()) {
            $this->warn('  Laravel Toast is not installed yet.');
            $this->newLine();
            $this->line('  Run the install command first:');
            $this->line('    <comment>php artisan toast:install</comment>');

            return self::FAILURE;
        }

        $interactive = !$this->option('css') && !$this->option('frontend') && !$this->settingsRequested();
        $selection = $interactive ? $this->promptFrameworks() : $this->flaggedFrameworks();
        if ($selection === false) {
            return self::FAILURE;
        }

        $settings = $this->resolveSettingsOptions($interactive);
        if ($settings === false) {
            return self::FAILURE;
        }

        $this->applyFrameworks($selection['css'], $selection['frontend']);

        if ($settings !== null) {
            $this->applySettings($settings);
        }

        info('Run: php artisan view:clear && npm run build');

        return self::SUCCESS;
    }

    /**
     * @return array{css: string|null, frontend: string|null}|false
     */
    protected function flaggedFrameworks(): array|false
    {
        $css = $this->option('css');
        $frontend = $this->option('frontend');

        if (!$this->frameworkFlagsAreValid($css, $frontend)) {
            return false;
        }

        return ['css' => $css, 'frontend' => $frontend];
    }

    protected function applyFrameworks(?string $css, ?string $frontend): void
    {
        if ($css) {
            $this->setCssFramework($css);
            info("CSS framework updated to: {$css}");
        }

        if ($frontend) {
            $this->setFrontendFramework($frontend);
            info("Frontend framework updated to: {$frontend}");
        }
    }

    protected function isInstalled(): bool
    {
        return file_exists(config_path('toast.php'));
    }
}
