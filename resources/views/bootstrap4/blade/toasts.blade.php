@php
    use Jeremykenedy\LaravelToast\Support\ToastAnimations;

    $toastManager = app(\Jeremykenedy\LaravelToast\Services\ToastManager::class);
    if (config('toast.convert_flash', true)) { $toastManager->convertFlashMessages(); }
    $toasts = $toastManager->get();
    $globalPosition = $toastManager->position();
    $stack = config('toast.stack', true);
    $positionMap = [
        'top-left' => 'top:0.5rem;left:0.5rem;', 'top-center' => 'top:0.5rem;left:50%;transform:translateX(-50%);',
        'top-right' => 'top:0.5rem;right:0.5rem;', 'bottom-right' => 'bottom:0.5rem;right:0.5rem;',
        'bottom-left' => 'bottom:0.5rem;left:0.5rem;', 'bottom-center' => 'bottom:0.5rem;left:50%;transform:translateX(-50%);',
    ];
    $grouped = [];
    $displayToasts = $stack ? $toasts : (count($toasts) ? [end($toasts)] : []);
    foreach ($displayToasts as $t) {
        $pos = $t['position'] ?? $globalPosition;
        if (!isset($positionMap[$pos])) $pos = 'top-right';
        $grouped[$pos][] = $t;
    }
    $typeMap = ['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info'];
    $iconMap = [
        'success'=>'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>',
        'error'=>'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/></svg>',
        'warning'=>'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>',
        'info'=>'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>',
    ];
@endphp
@if(count($displayToasts) > 0)
{!! ToastAnimations::styleTag() !!}
<style id="toast-bs4-theme">
.dark .alert-success,body.dark .alert-success{background-color:#064e3b!important;border-color:#047857!important;color:#d1fae5!important}
.dark .alert-danger,body.dark .alert-danger{background-color:#7f1d1d!important;border-color:#b91c1c!important;color:#fee2e2!important}
.dark .alert-warning,body.dark .alert-warning{background-color:#78350f!important;border-color:#b45309!important;color:#fef3c7!important}
.dark .alert-info,body.dark .alert-info{background-color:#1e3a5f!important;border-color:#1d4ed8!important;color:#dbeafe!important}
@media(prefers-color-scheme:dark){.alert-success{background-color:#064e3b!important;border-color:#047857!important;color:#d1fae5!important}.alert-danger{background-color:#7f1d1d!important;border-color:#b91c1c!important;color:#fee2e2!important}.alert-warning{background-color:#78350f!important;border-color:#b45309!important;color:#fef3c7!important}.alert-info{background-color:#1e3a5f!important;border-color:#1d4ed8!important;color:#dbeafe!important}}
</style>
@foreach($grouped as $pos => $posToasts)
<div style="position:fixed;{{ $positionMap[$pos] }} z-index:9999; width:min(400px, calc(100vw - 1rem)); pointer-events:none;">
    @foreach($posToasts as $toast)
    @php
        $bs4Type = $typeMap[$toast['type']] ?? 'info';
        $enterStyle = '';
        if (($toast['enter_animation'] ?? 'none') !== 'none') {
            $enterStyle = 'animation:toast-enter-' . $toast['enter_animation'] . ' ' . ($toast['enter_duration'] ?? 0.5) . 's ease forwards;';
        }
        $opacityStyle = (($toast['opacity'] ?? 1) < 1) ? 'opacity:' . $toast['opacity'] . ';' : '';
        $borderStyle = (($toast['show_border'] ?? true) === false) ? 'border:none;' : '';
    @endphp
    <div class="alert alert-{{ $bs4Type }} alert-dismissible fade show mb-2 shadow-sm"
         style="overflow:hidden;cursor:default;pointer-events:auto;word-break:break-word;{{ $opacityStyle }}{{ $borderStyle }}{{ $enterStyle }}"
         role="alert"
         aria-live="{{ $toast['type'] === 'error' ? 'assertive' : 'polite' }}"
         aria-atomic="true"
         dir="{{ $toast['dir'] ?? 'ltr' }}"
         id="toast-{{ $toast['id'] }}"
         data-laravel-toast="blade"
         data-auto-dismiss="{{ ($toast['auto_dismiss'] ?? true) ? 'true' : 'false' }}"
         data-duration="{{ $toast['duration'] }}"
         data-pause-on-hover="{{ ($toast['pause_on_hover'] ?? true) ? 'true' : 'false' }}"
         data-exit-animation="{{ $toast['exit_animation'] ?? 'none' }}"
         data-exit-duration="{{ $toast['exit_duration'] ?? 0.5 }}">
        @if(($toast['auto_dismiss'] ?? true) && ($toast['show_progress'] ?? true) !== false && $toast['duration'] > 0 && ($toast['progress_position'] ?? 'top') === 'top')
        <div style="height:3px;background:rgba(0,0,0,0.1);margin:-.75rem -1.25rem .5rem;"><div class="toast-progress-bar" style="height:100%;width:100%;background:rgba(0,0,0,0.25);transition:none;{{ ($toast['progress_direction'] ?? 'rtl') === 'rtl' ? 'margin-left:auto;' : '' }}" data-duration="{{ $toast['duration'] }}"></div></div>
        @endif
        <div class="d-flex align-items-start">
            @if(($toast['show_icon'] ?? true) !== false)
                <span class="mr-2 flex-shrink-0">{!! $toast['custom_icon'] ?? ($iconMap[$toast['type']] ?? $iconMap['info']) !!}</span>
            @endif
            <div class="flex-grow-1" style="cursor:default;min-width:0;">
                @if($toast['title']) <strong>{{ $toast['title'] }}</strong><br> @endif
                {{ $toast['message'] }}
            </div>
        </div>
        @if(($toast['show_close'] ?? true) !== false)
        <button type="button" class="close" style="cursor:pointer;" data-dismiss="alert" aria-label="{{ __('toast::toast.dismiss') }}"><span aria-hidden="true">&times;</span></button>
        @endif
        @if(($toast['auto_dismiss'] ?? true) && ($toast['show_progress'] ?? true) !== false && $toast['duration'] > 0 && ($toast['progress_position'] ?? 'top') !== 'top')
        <div style="height:3px;background:rgba(0,0,0,0.1);margin:.5rem -1.25rem -.75rem;"><div class="toast-progress-bar" style="height:100%;width:100%;background:rgba(0,0,0,0.25);transition:none;{{ ($toast['progress_direction'] ?? 'rtl') === 'rtl' ? 'margin-left:auto;' : '' }}" data-duration="{{ $toast['duration'] }}"></div></div>
        @endif
    </div>
    @endforeach
</div>
@endforeach
<script>
document.addEventListener('DOMContentLoaded', function() {
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function dismiss(el) {
        if (el.dataset.toastDismissing === 'true') return;
        el.dataset.toastDismissing = 'true';
        var exitAnim = el.dataset.exitAnimation || 'none';
        var exitDur = parseFloat(el.dataset.exitDuration) || 0.5;
        if (exitAnim !== 'none' && !reduceMotion) {
            el.style.animation = 'toast-' + exitAnim + ' ' + exitDur + 's ease forwards';
            setTimeout(function() { el.remove(); }, exitDur * 1000);
        } else {
            el.classList.remove('show');
            setTimeout(function() { el.remove(); }, 150);
        }
    }

    document.querySelectorAll('[data-laravel-toast="blade"][data-auto-dismiss="true"]').forEach(function(el) {
        // A duration of 0 means the toast stays until it is dismissed by hand.
        var duration = parseInt(el.dataset.duration, 10);
        if (!isFinite(duration) || duration <= 0) return;

        var pauseOnHover = el.dataset.pauseOnHover === 'true';
        var bar = el.querySelector('.toast-progress-bar');
        var start = Date.now(), elapsed = 0, pausedAt = 0;
        var running = true, hovered = false, focused = false;

        function tick() {
            if (!running || el.dataset.toastDismissing === 'true' || !el.isConnected) return;
            var e = Date.now() - start - elapsed;
            if (bar) bar.style.width = Math.max(0, 100 - (e / duration * 100)) + '%';
            if (e >= duration) dismiss(el); else requestAnimationFrame(tick);
        }

        // Hover and focus are tracked apart so leaving one does not restart the
        // countdown while the other is still holding it.
        function pause() {
            if (!running) return;
            running = false;
            pausedAt = Date.now();
        }

        function resume() {
            if (running || hovered || focused) return;
            elapsed += Date.now() - pausedAt;
            running = true;
            requestAnimationFrame(tick);
        }

        if (pauseOnHover) {
            el.addEventListener('mouseenter', function() { hovered = true; pause(); });
            el.addEventListener('mouseleave', function() { hovered = false; resume(); });
            el.addEventListener('focusin', function() { focused = true; pause(); });
            el.addEventListener('focusout', function() { focused = false; resume(); });
        }

        requestAnimationFrame(tick);
    });

    // Bootstrap 4 needs jQuery for data-dismiss, so handle the click here too.
    // Scoped to this package's alerts so a host application's own are left alone.
    document.querySelectorAll('[data-laravel-toast="blade"] .close').forEach(function(btn) {
        btn.addEventListener('click', function(event) {
            var el = btn.closest('[data-laravel-toast="blade"]');
            if (!el) return;
            event.preventDefault();
            event.stopPropagation();
            dismiss(el);
        });
    });
});
</script>
@endif
