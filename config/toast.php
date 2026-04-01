<?php

return [

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

    // Session key for flash toast data
    'session_key' => 'toast_notifications',

    // Convert standard flash messages to toasts
    'convert_flash' => true,

    // Real-time broadcasting
    'broadcast' => [
        'enabled' => (bool) env('TOAST_BROADCAST_ENABLED', false),
        'channel' => 'toast.{userId}',
    ],

];
