<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Livewire;

use Jeremykenedy\LaravelToast\Support\ToastColors;
use Jeremykenedy\LaravelToast\Support\ToastSettings as Settings;
use Livewire\Component;

class ToastSettings extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $options = [];

    /**
     * Every type, mode and part is present; an empty string means Default.
     *
     * @var array<string, array<string, array<string, string>>>
     */
    public array $colors = [];

    public function mount(): void
    {
        $this->authorizeManage();
        $this->fillFromSaved();
    }

    public function save(): void
    {
        $this->authorizeManage();

        if (!Settings::ready()) {
            $this->dispatch('toast-error', message: __('toast::toast.settings_not_ready'));

            return;
        }

        $validated = $this->validate(Settings::rules());

        Settings::save($validated);
        $this->fillFromSaved();
        $this->dispatch('toast-success', message: __('toast::toast.settings_saved'));
    }

    public function resetAll(): void
    {
        $this->authorizeManage();

        if (!Settings::ready()) {
            $this->dispatch('toast-error', message: __('toast::toast.settings_not_ready'));

            return;
        }

        Settings::reset();
        $this->fillFromSaved();
        $this->dispatch('toast-success', message: __('toast::toast.settings_reset'));
    }

    public function clearColor(string $type, string $mode, string $part): void
    {
        if (isset($this->colors[$type][$mode][$part])) {
            $this->colors[$type][$mode][$part] = '';
        }
    }

    public function render()
    {
        return view('toast-livewire::toast-settings', [
            'enabled'    => Settings::enabled(),
            'ready'      => Settings::ready(),
            'fields'     => Settings::fields(),
            'types'      => ToastColors::TYPES,
            'parts'      => ToastColors::parts(),
            'previewCss' => $this->previewCss(),
        ]);
    }

    private function previewCss(): string
    {
        $css = ToastColors::css($this->colors, [
            'light' => '[data-toast-preview="light"] ',
            'dark'  => '[data-toast-preview="dark"] ',
        ]);
        $hidden = [
            'show_icons'    => '[data-toast-preview] [data-toast-part="icon"]{display:none}',
            'show_close'    => '[data-toast-preview] [data-toast-part="close"]{display:none}',
            'show_progress' => '[data-toast-preview] [data-toast-part="track"]{display:none}',
            'show_border'   => '[data-toast-preview] [data-laravel-toast]{border-width:0!important}',
        ];

        foreach ($hidden as $option => $rule) {
            if (empty($this->options[$option])) {
                $css .= "\n".$rule;
            }
        }

        $opacity = (float) ($this->options['opacity'] ?? 1);

        return $opacity < 1 ? $css."\n".'[data-toast-preview] [data-laravel-toast]{opacity:'.$opacity.'}' : $css;
    }

    private function fillFromSaved(): void
    {
        $current = Settings::current();
        $this->options = $current['options'];
        $this->colors = [];

        foreach (ToastColors::TYPES as $type) {
            foreach (ToastColors::MODES as $mode) {
                foreach (ToastColors::parts() as $part) {
                    $this->colors[$type][$mode][$part] = $current['colors'][$type][$mode][$part] ?? '';
                }
            }
        }
    }

    private function authorizeManage(): void
    {
        abort_unless(Settings::canManage(auth()->user()), 403);
    }
}
