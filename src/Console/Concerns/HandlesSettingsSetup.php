<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Console\Concerns;

use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

/**
 * Opt-in setup for the notification settings page. Every step is behind a flag
 * or an explicit prompt answer, so a plain install changes nothing here.
 */
trait HandlesSettingsSetup
{
    protected function settingsRequested(): bool
    {
        return $this->option('settings') || $this->option('settings-page')
            || $this->option('settings-layout') !== null || $this->option('settings-middleware') !== null
            || $this->option('settings-gate') !== null || $this->option('settings-section') !== null;
    }

    /**
     * @return array<string, mixed>|null|false null when untouched, false when invalid
     */
    protected function resolveSettingsOptions(bool $promptWhenInteractive): array|null|false
    {
        if (!$this->settingsRequested()) {
            if (!$promptWhenInteractive || $this->option('no-interaction') || !confirm('Add the notification settings page (color pickers and options saved to the database)?', false)) {
                return null;
            }

            return $this->validateSettings($this->promptSettings());
        }

        return $this->validateSettings([
            'page'       => (bool) $this->option('settings-page'),
            'layout'     => $this->option('settings-layout'),
            'section'    => $this->option('settings-section') ?: 'content',
            'middleware' => $this->option('settings-middleware') ?: 'web,auth',
            'gate'       => $this->option('settings-gate') ?: 'manage-toast-settings',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function promptSettings(): array
    {
        $page = confirm('Publish a full settings page at /toast/settings?', true);

        return [
            'page'       => $page,
            'layout'     => $page ? (text('Blade layout the page should extend (blank for a standalone page)', 'layouts.app') ?: null) : null,
            'section'    => $page ? (text('Section name that layout yields', 'content', 'content') ?: 'content') : 'content',
            'middleware' => text('Route middleware, comma separated', 'web,auth', 'web,auth') ?: 'web,auth',
            'gate'       => text('Gate that decides who can change settings', 'manage-toast-settings', 'manage-toast-settings') ?: 'manage-toast-settings',
        ];
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>|false
     */
    protected function validateSettings(array $settings): array|false
    {
        $layout = $settings['layout'] ?? null;

        if ($layout !== null && (!preg_match('/^[A-Za-z0-9_.:-]+$/', $layout) || !view()->exists($layout))) {
            $this->error("Layout view not found: {$layout}. Use a Blade view name such as layouts.app.");

            return false;
        }

        if (!preg_match('/^[A-Za-z0-9_.:,\\\\-]+$/', (string) $settings['middleware'])) {
            $this->error('Invalid middleware list. Use names separated by commas, for example web,auth.');

            return false;
        }

        if (!preg_match('/^[A-Za-z0-9_.:-]+$/', (string) $settings['section']) || !preg_match('/^[A-Za-z0-9_.:-]+$/', (string) $settings['gate'])) {
            $this->error('Invalid section or gate name.');

            return false;
        }

        return $settings;
    }

    /**
     * @param array<string, mixed> $settings
     */
    protected function applySettings(array $settings): void
    {
        $this->updateEnvValue('TOAST_SETTINGS_ENABLED', 'true');
        $this->updateEnvValue('TOAST_SETTINGS_PAGE', $settings['page'] ? 'true' : 'false');
        $this->updateEnvValue('TOAST_SETTINGS_MIDDLEWARE', (string) $settings['middleware']);
        $this->updateEnvValue('TOAST_SETTINGS_GATE', (string) $settings['gate']);
        $this->updateEnvValue('TOAST_SETTINGS_SECTION', (string) $settings['section']);

        if ($settings['page']) {
            $this->updateEnvValue('TOAST_SETTINGS_VIEW', 'toast.settings');
        }

        if ($settings['layout'] !== null) {
            $this->updateEnvValue('TOAST_SETTINGS_LAYOUT', (string) $settings['layout']);
        }

        config([
            'toast.settings.enabled'    => true,
            'toast.settings.page'       => (bool) $settings['page'],
            'toast.settings.view'       => $settings['page'] ? 'toast.settings' : null,
            'toast.settings.middleware' => array_values(array_filter(explode(',', (string) $settings['middleware']))),
            'toast.settings.gate'       => $settings['gate'],
            'toast.settings.section'    => $settings['section'],
            'toast.settings.layout'     => $settings['layout'],
        ]);

        if (!$this->settingsMigrationPublished()) {
            $this->call('vendor:publish', ['--tag' => 'toast-settings-migrations']);
        }

        if ($settings['page']) {
            $this->call('vendor:publish', ['--tag' => 'toast-settings-page', '--force' => true]);
        }

        $this->clearCaches();
        $this->showSettingsSummary($settings);
    }

    protected function settingsMigrationPublished(): bool
    {
        return File::isDirectory(database_path('migrations'))
            && count(File::glob(database_path('migrations/*_create_toast_settings_table.php'))) > 0;
    }

    /**
     * @param array<string, mixed> $settings
     */
    protected function showSettingsSummary(array $settings): void
    {
        $this->newLine();
        $this->line("  \033[32mNotification settings enabled.\033[0m");
        $this->newLine();
        $this->line('  1. Review the published migration, then run: '."\033[33mphp artisan migrate\033[0m");
        $this->line('  2. Define who may change settings in a service provider:');
        $this->line("     \033[33mGate::define('{$settings['gate']}', fn (\$user) => \$user->isAdmin());\033[0m");
        $this->line('     An undefined gate denies everyone.');

        foreach ($this->settingsPlacementHint($settings) as $line) {
            $this->line($line);
        }

        $this->newLine();
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return list<string>
     */
    private function settingsPlacementHint(array $settings): array
    {
        $pageHint = [
            '  3. Open '."\033[33m/toast/settings\033[0m".' (middleware: '.$settings['middleware'].')',
            '     Page view published to resources/views/toast/settings.blade.php',
        ];
        $includeHint = ['  3. Add '."\033[33m@toastSettings\033[0m".' (or '."\033[33m<livewire:toast-settings />\033[0m".') where you want the panel.'];

        return $settings['page'] ? $pageHint : $includeHint;
    }
}
