<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Console\Concerns;

use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;

use function Laravel\Prompts\select;

trait HasInstallPrompts
{
    protected static array $font = [
        'A' => ['  ██  ', ' ████ ', '██  ██', '██████', '██  ██'],
        'B' => ['█████ ', '██  ██', '█████ ', '██  ██', '█████ '],
        'C' => [' ████ ', '██    ', '██    ', '██    ', ' ████ '],
        'D' => ['████  ', '██  ██', '██  ██', '██  ██', '████  '],
        'E' => ['██████', '██    ', '████  ', '██    ', '██████'],
        'F' => ['██████', '██    ', '████  ', '██    ', '██    '],
        'G' => [' ████ ', '██    ', '██ ███', '██  ██', ' ████ '],
        'H' => ['██  ██', '██  ██', '██████', '██  ██', '██  ██'],
        'I' => ['██████', '  ██  ', '  ██  ', '  ██  ', '██████'],
        'J' => ['   ███', '    ██', '    ██', '██  ██', ' ████ '],
        'K' => ['██  ██', '██ ██ ', '████  ', '██ ██ ', '██  ██'],
        'L' => ['██    ', '██    ', '██    ', '██    ', '██████'],
        'M' => ['██   ██', '███ ███', '██ █ ██', '██   ██', '██   ██'],
        'N' => ['██  ██', '███ ██', '██████', '██ ███', '██  ██'],
        'O' => [' ████ ', '██  ██', '██  ██', '██  ██', ' ████ '],
        'P' => ['█████ ', '██  ██', '█████ ', '██    ', '██    '],
        'Q' => [' ████ ', '██  ██', '██  ██', '██ ██ ', ' ██ ██'],
        'R' => ['█████ ', '██  ██', '█████ ', '██ ██ ', '██  ██'],
        'S' => [' ████ ', '██    ', ' ████ ', '    ██', ' ████ '],
        'T' => ['██████', '  ██  ', '  ██  ', '  ██  ', '  ██  '],
        'U' => ['██  ██', '██  ██', '██  ██', '██  ██', ' ████ '],
        'V' => ['██  ██', '██  ██', '██  ██', ' ████ ', '  ██  '],
        'W' => ['██   ██', '██   ██', '██ █ ██', '███ ███', '██   ██'],
        'X' => ['██  ██', ' ████ ', '  ██  ', ' ████ ', '██  ██'],
        'Y' => ['██  ██', ' ████ ', '  ██  ', '  ██  ', '  ██  '],
        'Z' => ['██████', '   ██ ', '  ██  ', ' ██   ', '██████'],
        '2' => [' ████ ', '    ██', ' ████ ', '██    ', '██████'],
        '-' => ['      ', '      ', ' ████ ', '      ', '      '],
        ' ' => ['   ', '   ', '   ', '   ', '   '],
    ];

    protected function renderBanner(string $name): void
    {
        $chars = str_split(strtoupper($name));
        $lines = ['', '', '', '', ''];

        foreach ($chars as $char) {
            $glyph = self::$font[$char] ?? self::$font[' '];
            for ($i = 0; $i < 5; $i++) {
                $lines[$i] .= $glyph[$i].' ';
            }
        }

        $this->newLine();

        $palettes = [
            ['34', '35', '94', '95', '96'],   // Blue/Purple/Cyan
            ['31', '91', '33', '93', '31'],   // Red/Yellow
            ['32', '92', '36', '96', '32'],   // Green/Cyan
            ['33', '93', '91', '31', '33'],   // Yellow/Red
            ['35', '95', '34', '94', '35'],   // Magenta/Blue
            ['36', '96', '92', '32', '36'],   // Cyan/Green
            ['91', '93', '92', '96', '94'],   // Bright rainbow
            ['95', '35', '34', '94', '96'],   // Pink to Cyan
        ];
        $colors = $palettes[array_rand($palettes)];

        foreach ($lines as $i => $line) {
            $color = $colors[$i % count($colors)];
            $this->line("  \033[{$color}m{$line}\033[0m");
        }

        $this->newLine();
    }

    /**
     * Run the stepped framework selection flow with back navigation and confirmation.
     *
     * @return array{css: string, frontend: string}|false
     */
    protected function promptFrameworks(): array|false
    {
        $css = $this->option('css');
        $frontend = $this->option('frontend');

        // Validated up front so a single invalid flag cannot slip through the
        // non interactive path and be written to .env as a success.
        if ($css && !in_array($css, ToastServiceProvider::CSS_FRAMEWORKS)) {
            $this->error("Invalid CSS framework: {$css}. Use: ".implode(', ', ToastServiceProvider::CSS_FRAMEWORKS));

            return false;
        }

        if ($frontend && !in_array($frontend, ToastServiceProvider::FRONTENDS)) {
            $this->error("Invalid frontend: {$frontend}. Use: ".implode(', ', ToastServiceProvider::FRONTENDS));

            return false;
        }

        if ($css && $frontend) {
            return ['css' => $css, 'frontend' => $frontend];
        }

        if ($this->option('no-interaction')) {
            return [
                'css'      => $css ?: ToastServiceProvider::cssFramework(),
                'frontend' => $frontend ?: ToastServiceProvider::frontend(),
            ];
        }

        while (true) {
            $cssResult = $this->promptCssFramework();
            if ($cssResult === false) {
                return false;
            }

            $frontendResult = $this->promptFrontendFramework();
            if ($frontendResult === false) {
                return false;
            }
            if ($frontendResult === '__back__') {
                continue;
            }

            $confirmation = $this->promptConfirmation($cssResult, $frontendResult);

            if ($confirmation === 'confirm') {
                return ['css' => $cssResult, 'frontend' => $frontendResult];
            }

            if ($confirmation === 'cancel') {
                $this->info('  Cancelled. No changes were made.');

                return false;
            }

            // 'restart' loops back to the top
        }
    }

    protected function promptCssFramework(): string|false
    {
        $valid = ToastServiceProvider::CSS_FRAMEWORKS;
        $css = $this->option('css');

        if ($css) {
            if (!in_array($css, $valid)) {
                $this->error("Invalid CSS framework: {$css}. Use: ".implode(', ', $valid));

                return false;
            }

            return $css;
        }

        if ($this->option('no-interaction')) {
            return ToastServiceProvider::cssFramework();
        }

        return select(
            label: 'Which CSS framework would you like to use?',
            options: [
                'tailwind'   => 'Tailwind CSS v4',
                'bootstrap5' => 'Bootstrap 5',
                'bootstrap4' => 'Bootstrap 4',
            ],
            default: ToastServiceProvider::cssFramework(),
        );
    }

    protected function promptFrontendFramework(): string|false
    {
        $valid = ToastServiceProvider::FRONTENDS;
        $frontend = $this->option('frontend');

        if ($frontend) {
            if (!in_array($frontend, $valid)) {
                $this->error("Invalid frontend: {$frontend}. Use: ".implode(', ', $valid));

                return false;
            }

            return $frontend;
        }

        if ($this->option('no-interaction')) {
            return ToastServiceProvider::frontend();
        }

        return select(
            label: 'Which frontend framework would you like to use?',
            options: [
                '__back__'   => "\033[90m< Back to CSS selection\033[0m",
                'blade'      => 'Blade + Alpine.js',
                'livewire'   => 'Livewire 3',
                'vue'        => 'Vue 3',
                'react'      => 'React 18',
                'svelte'     => 'Svelte 4',
            ],
            default: ToastServiceProvider::frontend(),
        );
    }

    protected function promptConfirmation(string $css, string $frontend): string
    {
        $cssLabels = [
            'tailwind'   => 'Tailwind CSS v4',
            'bootstrap5' => 'Bootstrap 5',
            'bootstrap4' => 'Bootstrap 4',
        ];

        $frontendLabels = [
            'blade'    => 'Blade + Alpine.js',
            'livewire' => 'Livewire 3',
            'vue'      => 'Vue 3',
            'react'    => 'React 18',
            'svelte'   => 'Svelte 4',
        ];

        $this->newLine();
        $this->line("  \033[1mYour selections:\033[0m");
        $this->line("  \033[90mCSS:\033[0m       ".($cssLabels[$css] ?? $css));
        $this->line("  \033[90mFrontend:\033[0m  ".($frontendLabels[$frontend] ?? $frontend));
        $this->newLine();

        return select(
            label: 'Continue with these settings?',
            options: [
                'confirm' => 'Confirm and continue',
                'restart' => 'Start over',
                'cancel'  => 'Cancel and exit',
            ],
            default: 'confirm',
        );
    }

    protected function showSummary(string $packageName, string $css, string $frontend): void
    {
        $cssLabels = [
            'tailwind'   => 'Tailwind CSS v4',
            'bootstrap5' => 'Bootstrap 5',
            'bootstrap4' => 'Bootstrap 4',
        ];

        $frontendLabels = [
            'blade'    => 'Blade + Alpine.js',
            'livewire' => 'Livewire 3',
            'vue'      => 'Vue 3',
            'react'    => 'React 18',
            'svelte'   => 'Svelte 4',
        ];

        $cssLabel = $cssLabels[$css] ?? $css;
        $feLabel = $frontendLabels[$frontend] ?? $frontend;

        $this->newLine();
        $this->line("  \033[32m{$packageName} installed successfully.\033[0m");
        $this->newLine();
        $this->line("  \033[90mCSS:\033[0m       {$cssLabel}");
        $this->line("  \033[90mFrontend:\033[0m  {$feLabel}");
        $this->newLine();
        if ($css === 'tailwind') {
            $this->line('  Add this source to resources/css/app.css:');
            $this->line('  @source "../../vendor/jeremykenedy/laravel-toast/resources";');
            $this->newLine();
        }
        $this->line("  Run: \033[33mnpm run build\033[0m");
        $this->newLine();
    }
}
