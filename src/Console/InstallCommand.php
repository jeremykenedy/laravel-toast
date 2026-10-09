<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Console;

use Illuminate\Console\Command;
use Jeremykenedy\LaravelToast\Console\Concerns\HandlesFrameworkSetup;
use Jeremykenedy\LaravelToast\Console\Concerns\HandlesSettingsSetup;
use Jeremykenedy\LaravelToast\Console\Concerns\HasInstallPrompts;

class InstallCommand extends Command
{
    use HandlesFrameworkSetup;
    use HandlesSettingsSetup;
    use HasInstallPrompts;

    protected $signature = 'toast:install
        {--css= : CSS framework (tailwind, bootstrap5, bootstrap4)}
        {--frontend= : Frontend framework (blade, livewire, vue, react, svelte)}
        {--force : Skip confirmation when reinstalling}
        {--settings : Enable the notification settings (publishes the migration)}
        {--settings-page : Also publish a full settings page}
        {--settings-layout= : Blade layout the settings page extends, for example layouts.app}
        {--settings-section= : Section name that layout yields (default: content)}
        {--settings-middleware= : Comma separated route middleware (default: web,auth)}
        {--settings-gate= : Gate that authorizes changes (default: manage-toast-settings)}';

    protected $description = 'Install and configure the Laravel Toast package';

    public function handle(): int
    {
        $this->renderBanner('TOAST');

        if ($this->isAlreadyInstalled() && !$this->option('force')) {
            $this->warn('  Laravel Toast is already installed.');
            $this->newLine();
            $this->line('  To change frameworks, use the update command instead:');
            $this->line('    <comment>php artisan toast:update</comment>');
            $this->newLine();
            $this->line('  To switch a single setting quickly:');
            $this->line('    <comment>php artisan toast:switch --css=bootstrap5</comment>');
            $this->newLine();
            $this->warn('  Reinstalling will overwrite your published config.');
            $this->warn('  This is a destructive action that resets all package settings.');
            $this->newLine();

            if ($this->option('no-interaction')) {
                $this->error('  Already installed. Use --force to reinstall non-interactively.');

                return self::FAILURE;
            }

            $confirm = $this->ask('  Type "yes" to reinstall from scratch, or any other key to cancel');

            if ($confirm !== 'yes') {
                $this->info('  Cancelled. No changes were made.');

                return self::SUCCESS;
            }

            $this->newLine();
        }

        $result = $this->promptFrameworks();
        if ($result === false) {
            return self::FAILURE;
        }

        $settings = $this->resolveSettingsOptions(true);
        if ($settings === false) {
            return self::FAILURE;
        }

        $this->call('vendor:publish', ['--tag' => 'toast-config', '--force' => true]);

        $this->setCssFramework($result['css']);
        $this->setFrontendFramework($result['frontend']);

        $this->showSummary('Laravel Toast', $result['css'], $result['frontend']);

        if ($settings !== null) {
            $this->applySettings($settings);
        }

        return self::SUCCESS;
    }

    protected function isAlreadyInstalled(): bool
    {
        return file_exists(config_path('toast.php'));
    }
}
