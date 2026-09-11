{{--
    Auto-dismiss timers for the Livewire container.

    Rendered on every pass, including the empty one. A script morphed in later
    does not execute, so emitting this only alongside the first toast would
    leave that toast without a timer.
--}}
<script>
(function () {
    if (window.__laravelToastTimers) {
        window.__laravelToastTimers();

        return;
    }

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var hooked = false;

    // Livewire morphs against server markup, which would strip a data attribute
    // used as a bound flag and let the next scan add a second timer.
    var bound = new WeakSet();

    function componentFor(el) {
        var root = el.closest('[wire\\:id]');

        return root && window.Livewire && typeof window.Livewire.find === 'function'
            ? window.Livewire.find(root.getAttribute('wire:id'))
            : null;
    }

    // Removing the node alone leaves the toast in the component's $toasts and
    // the next morph renders it straight back, so go through the component.
    function drop(el, component) {
        var live = component || componentFor(el);

        if (live) {
            live.call('dismiss', el.id.replace('lw-toast-', ''));

            return;
        }

        el.remove();
    }

    function dismiss(el) {
        if (el.dataset.toastDismissing === 'true') return;
        el.dataset.toastDismissing = 'true';

        // Resolved now, because a morph can detach the node before the exit
        // animation finishes and closest() would then find nothing.
        var component = componentFor(el);

        // The node lingers until the server responds, so stop it intercepting
        // clicks while it is on its way out.
        el.style.pointerEvents = 'none';

        var exitAnim = el.dataset.exitAnimation || 'none';
        var exitDur = parseFloat(el.dataset.exitDuration) || 0.5;

        if (exitAnim !== 'none' && !reduceMotion) {
            el.style.animation = 'toast-' + exitAnim + ' ' + exitDur + 's ease forwards';
            setTimeout(function () { drop(el, component); }, exitDur * 1000);
        } else {
            el.style.opacity = '0';
            setTimeout(function () { drop(el, component); }, 200);
        }
    }

    function bind(el) {
        if (bound.has(el)) return;
        bound.add(el);

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
