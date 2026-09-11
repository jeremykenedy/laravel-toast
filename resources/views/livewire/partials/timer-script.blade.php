{{--
    Auto-dismiss timers for the Livewire container.

    Livewire swaps DOM on every round trip, so binding once on DOMContentLoaded
    misses every toast dispatched after first paint. This registers once per
    page and re-scans after each Livewire morph, marking elements it has already
    wired so a morph never starts a second timer for the same toast.
--}}
<script>
(function () {
    if (window.__laravelToastTimers) {
        window.__laravelToastTimers();

        return;
    }

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function dismiss(el) {
        if (el.dataset.toastDismissing === 'true') return;
        el.dataset.toastDismissing = 'true';
        var exitAnim = el.dataset.exitAnimation || 'none';
        var exitDur = parseFloat(el.dataset.exitDuration) || 0.5;
        if (exitAnim !== 'none' && !reduceMotion) {
            el.style.animation = 'toast-' + exitAnim + ' ' + exitDur + 's ease forwards';
            setTimeout(function () { el.remove(); }, exitDur * 1000);
        } else {
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 200);
        }
    }

    function bind(el) {
        if (el.dataset.toastBound === 'true') return;
        el.dataset.toastBound = 'true';

        var duration = parseInt(el.dataset.duration) || 5000;
        var pauseOnHover = el.dataset.pauseOnHover === 'true';
        var bar = el.querySelector('.toast-progress-bar');
        var start = Date.now(), paused = false, pausedAt = 0, elapsed = 0;

        function tick() {
            if (paused || el.dataset.toastDismissing === 'true' || !el.isConnected) return;
            var e = Date.now() - start - elapsed;
            if (bar) bar.style.width = Math.max(0, 100 - (e / duration * 100)) + '%';
            if (e >= duration) dismiss(el); else requestAnimationFrame(tick);
        }

        function pause() { paused = true; pausedAt = Date.now(); }
        function resume() { elapsed += Date.now() - pausedAt; paused = false; requestAnimationFrame(tick); }

        if (pauseOnHover) {
            el.addEventListener('mouseenter', pause);
            el.addEventListener('mouseleave', resume);
            el.addEventListener('focusin', pause);
            el.addEventListener('focusout', resume);
        }

        requestAnimationFrame(tick);
    }

    function scan() {
        document.querySelectorAll('[id^="lw-toast-"][data-auto-dismiss="true"]').forEach(bind);
    }

    window.__laravelToastTimers = scan;

    document.addEventListener('DOMContentLoaded', scan);
    document.addEventListener('livewire:navigated', scan);
    document.addEventListener('livewire:initialized', function () {
        if (window.Livewire && typeof window.Livewire.hook === 'function') {
            window.Livewire.hook('morphed', scan);
        }
        scan();
    });

    scan();
})();
</script>
