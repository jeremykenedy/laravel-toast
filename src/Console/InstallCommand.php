<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Console;

use Illuminate\Console\Command;
use Jeremykenedy\LaravelToast\Console\Concerns\HandlesFrameworkSetup;
use Jeremykenedy\LaravelToast\Console\Concerns\HasInstallPrompts;

class InstallCommand extends Command
{
    use HandlesFrameworkSetup;
    use HasInstallPrompts;

    protected $signature = 'toast:install
        {--css= : CSS framework (tailwind, bootstrap5, bootstrap4)}
        {--frontend= : Frontend framework (blade, livewire, vue, react, svelte)}
        {--force : Skip confirmation when reinstalling}';

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
            $this->warn('  Reinstalling will overwrite your config and published views.');
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

        $this->call('vendor:publish', ['--tag' => 'toast-config', '--force' => true]);

        $this->setCssFramework($result['css']);
        $this->setFrontendFramework($result['frontend']);

        $this->showSummary('Laravel Toast', $result['css'], $result['frontend']);

        return self::SUCCESS;
    }

    protected function isAlreadyInstalled(): bool
    {
        return file_exists(config_path('toast.php'));
    }
}
