{{--
    Auto-dismiss timers for the Livewire container.

    Livewire swaps DOM on every round trip, so binding once on DOMContentLoaded
    misses every toast dispatched after first paint. This registers once per
    page and re-scans after each morph.
--}}
<script>
(function () {
    if (window.__laravelToastTimers) {
        window.__laravelToastTimers();

        return;
    }

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var hooked = false;

    // Removing the node alone leaves the toast in the component's $toasts, and
    // the next morph renders it straight back. Dismiss through the component so
    // the server drops it too.
    function drop(el) {
        var root = el.closest('[wire\\:id]');
        var component = root && window.Livewire && typeof window.Livewire.find === 'function'
            ? window.Livewire.find(root.getAttribute('wire:id'))
            : null;

        if (component) {
            component.call('dismiss', el.id.replace('lw-toast-', ''));

            return;
        }

        el.remove();
    }

    function dismiss(el) {
        if (el.dataset.toastDismissing === 'true') return;
        el.dataset.toastDismissing = 'true';

        var exitAnim = el.dataset.exitAnimation || 'none';
        var exitDur = parseFloat(el.dataset.exitDuration) || 0.5;

        if (exitAnim !== 'none' && !reduceMotion) {
            el.style.animation = 'toast-' + exitAnim + ' ' + exitDur + 's ease forwards';
            setTimeout(function () { drop(el); }, exitDur * 1000);
        } else {
            el.style.opacity = '0';
            setTimeout(function () { drop(el); }, 200);
        }
    }

    function bind(el) {
        if (el.dataset.toastBound === 'true') return;
        el.dataset.toastBound = 'true';

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
            el.addEventListener('mouseenter', function () { hovered = true; pause(); });
            el.addEventListener('mouseleave', function () { hovered = false; resume(); });
            el.addEventListener('focusin', function () { focused = true; pause(); });
            el.addEventListener('focusout', function () { focused = false; resume(); });
        }

        requestAnimationFrame(tick);
    }

    function scan() {
        document.querySelectorAll('[data-laravel-toast="livewire"][data-auto-dismiss="true"]').forEach(bind);
    }

    // This partial first renders with the opening toast, which can be well after
    // livewire:initialized has fired, so try the hook now as well as on the event.
    function registerHook() {
        if (hooked || !window.Livewire || typeof window.Livewire.hook !== 'function') return;
        hooked = true;
        window.Livewire.hook('morphed', scan);
    }

    window.__laravelToastTimers = scan;

    document.addEventListener('DOMContentLoaded', scan);
    document.addEventListener('livewire:navigated', scan);
    document.addEventListener('livewire:initialized', function () {
        registerHook();
        scan();
    });

    registerHook();
    scan();
})();
</script>
