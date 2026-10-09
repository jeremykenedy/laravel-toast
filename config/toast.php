<?php

return [

    // Which CSS framework's views to render: "tailwind", "bootstrap5", "bootstrap4".
    //
    // Leave this null to defer to config('ui-kit.css_framework'), which is how
    // applications running jeremykenedy/laravel-ui-kit drive every package from
    // one place. Set TOAST_CSS to take control when using toast standalone.
    'css_framework' => env('TOAST_CSS'),

    // Which frontend you are using: "blade", "livewire", "vue", "react",
    // "svelte".
    //
    // This records your setup, it does not select anything at runtime. Blade
    // and Livewire resolve their own views and the Vue, React and Svelte
    // components are imported directly, so nothing reads this while rendering.
    // The install, update and switch commands read and write it. Same
    // precedence as css_framework: null defers to config('ui-kit.frontend').
    'frontend' => env('TOAST_FRONTEND'),

    // Where toasts appear: top-right, top-left, top-center,
    //                       bottom-right, bottom-left, bottom-center
    'position' => env('TOAST_POSITION', 'top-right'),

    // Text direction: "ltr" or "rtl"
    'dir' => env('TOAST_DIR', 'ltr'),

    // Auto-dismiss delay in milliseconds (0 = no auto-dismiss)
    'duration' => (int) env('TOAST_DURATION', 5000),

    // Max toasts shown at once (oldest removed first)
    'max_visible' => (int) env('TOAST_MAX_VISIBLE', 5),

    // Auto-dismiss toasts after duration expires
    'auto_dismiss' => (bool) env('TOAST_AUTO_DISMISS', true),

    // Pause countdown on hover
    'pause_on_hover' => (bool) env('TOAST_PAUSE_ON_HOVER', true),

    // Stack multiple toasts (false = replace previous)
    'stack' => (bool) env('TOAST_STACK', true),

    // Show type-specific icons
    'show_icons' => (bool) env('TOAST_SHOW_ICONS', true),

    // Show border around toasts
    'show_border' => (bool) env('TOAST_SHOW_BORDER', true),

    // Show close/dismiss button
    'show_close' => (bool) env('TOAST_SHOW_CLOSE', true),

    // Show animated progress bar
    'show_progress' => (bool) env('TOAST_SHOW_PROGRESS', true),

    // Progress bar direction: "rtl" or "ltr"
    'progress_direction' => env('TOAST_PROGRESS_DIRECTION', 'rtl'),

    // Progress bar position: "top" or "bottom"
    'progress_position' => env('TOAST_PROGRESS_POSITION', 'top'),

    // Toast opacity (0 to 1)
    'opacity' => (float) env('TOAST_OPACITY', 1),

    // Enter animation: "none", "fade",
    //   "slide-left", "slide-right", "slide-top", "slide-bottom",
    //   "bounce-left", "bounce-right", "bounce-top", "bounce-bottom",
    //   "shrink-left", "shrink-right", "shrink-top", "shrink-bottom", "shrink-center",
    //   "flip-left", "flip-right", "flip-top", "flip-bottom", "flip-center",
    //   "spin-left", "spin-right", "spin-top", "spin-bottom", "spin-center",
    //   "grow-left", "grow-right", "grow-top", "grow-bottom", "grow-center",
    //   "slam-left", "slam-right", "slam-top", "slam-bottom", "slam-center",
    //   "bounce-center", "fade-center",
    //   "wobble", "wobble-left", "wobble-right", "wobble-top", "wobble-bottom", "wobble-center",
    //   "slide", "bounce", "shrink", "flip", "spin", "grow", "slam", "wobble" (directionless defaults)
    'enter_animation' => env('TOAST_ENTER_ANIMATION', 'none'),

    // Enter animation duration in seconds
    'enter_duration' => (float) env('TOAST_ENTER_DURATION', 0.5),

    // Exit animation: "none", "fade",
    //   "slide-left", "slide-right", "slide-top", "slide-bottom",
    //   "bounce-left", "bounce-right", "bounce-top", "bounce-bottom",
    //   "shrink-left", "shrink-right", "shrink-top", "shrink-bottom", "shrink-center",
    //   "flip-left", "flip-right", "flip-top", "flip-bottom", "flip-center",
    //   "spin-left", "spin-right", "spin-top", "spin-bottom", "spin-center",
    //   "grow-left", "grow-right", "grow-top", "grow-bottom", "grow-center",
    //   "slam-left", "slam-right", "slam-top", "slam-bottom", "slam-center",
    //   "bounce-center", "fade-center",
    //   "wobble", "wobble-left", "wobble-right", "wobble-top", "wobble-bottom", "wobble-center",
    //   "slide", "bounce", "shrink", "flip", "spin", "grow", "slam", "wobble" (directionless defaults)
    'exit_animation' => env('TOAST_EXIT_ANIMATION', 'none'),

    // Exit animation duration in seconds
    'exit_duration' => (float) env('TOAST_EXIT_DURATION', 0.5),

    // Color overrides. Every part is optional; unset parts keep the framework's
    // own colors. Types: success, error, warning, info. Modes: light, dark.
    // Parts: background, text, border, icon, progress, track. Hex values only.
    //
    //   'colors' => [
    //       'success' => [
    //           'light' => ['background' => '#ecfdf5', 'text' => '#065f46'],
    //           'dark'  => ['background' => '#064e3b', 'text' => '#d1fae5'],
    //       ],
    //   ],
    //
    // Values saved from the settings page take precedence over this array.
    'colors' => [],

    // Settings page. Disabled by default; nothing is created or exposed until
    // you opt in. Run `php artisan toast:install --settings` or follow the
    // README to publish the migration, enable it and define the gate.
    'settings' => [
        // Turns on the routes, the saved-settings override and the UI.
        'enabled' => (bool) env('TOAST_SETTINGS_ENABLED', false),

        // Gate that decides who can view and change settings. An undefined
        // gate denies everyone.
        'gate' => env('TOAST_SETTINGS_GATE', 'manage-toast-settings'),

        // Database connection and table. Null uses the default connection.
        'connection' => env('TOAST_SETTINGS_CONNECTION'),
        'table'      => env('TOAST_SETTINGS_TABLE', 'toast_settings'),

        // Route group. The middleware list runs before the gate.
        'prefix'     => env('TOAST_SETTINGS_PREFIX', 'toast/settings'),
        'name'       => 'toast.settings.',
        'middleware' => array_values(array_filter(array_map('trim', explode(',', (string) env('TOAST_SETTINGS_MIDDLEWARE', 'web,auth'))))),

        // Register the full page route (GET {prefix}). The page renders inside
        // `layout`, a Blade layout from your app, using `section` as the
        // section name the layout yields. Null layout uses a standalone page.
        'page'    => (bool) env('TOAST_SETTINGS_PAGE', false),

        // View the page route renders. Null uses the package page. The install
        // command publishes your own copy to resources/views/toast/settings.blade.php
        // and sets this to "toast.settings" so you can edit it freely.
        'view'    => env('TOAST_SETTINGS_VIEW'),
        'layout'  => env('TOAST_SETTINGS_LAYOUT'),
        'section' => env('TOAST_SETTINGS_SECTION', 'content'),
    ],

    // Session key for flash toast data
    'session_key' => 'toast_notifications',

    // Convert standard flash messages to toasts
    'convert_flash' => true,

    // Real-time broadcasting
    'broadcast' => [
        'enabled' => (bool) env('TOAST_BROADCAST_ENABLED', false),
        'channel' => env('TOAST_BROADCAST_CHANNEL', 'toast.{userId}'),
    ],

];
