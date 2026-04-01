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
        {--frontend= : Frontend framework (blade, livewire, vue, react, svelte)}';

    protected $description = 'Install and configure the Laravel Toast package';

    public function handle(): int
    {
        $this->renderBanner('TOAST');

        $css = $this->promptCssFramework();
        if ($css === false) {
            return self::FAILURE;
        }
        $frontend = $this->promptFrontendFramework();
        if ($frontend === false) {
            return self::FAILURE;
        }

        $this->call('vendor:publish', ['--tag' => 'toast-config', '--force' => true]);

        $this->setCssFramework($css);
        $this->setFrontendFramework($frontend);

        $this->showSummary('Laravel Toast', $css, $frontend);

        return self::SUCCESS;
    }
}
