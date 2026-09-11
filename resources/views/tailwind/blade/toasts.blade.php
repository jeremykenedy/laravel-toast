@php
    use Jeremykenedy\LaravelToast\Support\ToastAnimations;

    $toastManager = app(\Jeremykenedy\LaravelToast\Services\ToastManager::class);
    if (config('toast.convert_flash', true)) {
        $toastManager->convertFlashMessages();
    }
    $toasts = $toastManager->get();
    $globalPosition = $toastManager->position();
    $stack = config('toast.stack', true);

    $positionMap = [
        'top-left' => 'top: 0.5rem; left: 0.5rem;',
        'top-center' => 'top: 0.5rem; left: 50%; transform: translateX(-50%);',
        'top-right' => 'top: 0.5rem; right: 0.5rem;',
        'bottom-right' => 'bottom: 0.5rem; right: 0.5rem;',
        'bottom-left' => 'bottom: 0.5rem; left: 0.5rem;',
        'bottom-center' => 'bottom: 0.5rem; left: 50%; transform: translateX(-50%);',
    ];

    $grouped = [];
    foreach ($toasts as $t) {
        $pos = $t['position'] ?? $globalPosition;
        if (!isset($positionMap[$pos])) $pos = 'top-right';
        $grouped[$pos][] = $t;
    }

    if (!$stack) {
        foreach ($grouped as $pos => $items) {
            $grouped[$pos] = [end($items)];
        }
    }
@endphp
@if(count($toasts) > 0)
{!! ToastAnimations::styleTag() !!}
@foreach($grouped as $pos => $posToasts)
@php $containerId = 'toast-container-' . str_replace(['-', ' '], '_', $pos); @endphp
<div
    id="{{ $containerId }}"
    x-data="toastContainer_{{ str_replace(['-', ' '], '_', $pos) }}()"
    style="position: fixed; {{ $positionMap[$pos] }} z-index: 9999; width: 24rem; max-width: calc(100vw - 1rem); display: flex; flex-direction: column; gap: 0.75rem; pointer-events: none;"
    role="status"
    aria-live="polite"
    aria-atomic="false"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            :data-toast-id="toast.id"
            x-show="toasts.find(t => t.id === toast.id)"
            x-cloak
            @mouseenter="toast.pause_on_hover && hold(toast.id, 'hover')"
            @mouseleave="toast.pause_on_hover && release(toast.id, 'hover')"
            @focusin="toast.pause_on_hover && hold(toast.id, 'focus')"
            @focusout="toast.pause_on_hover && release(toast.id, 'focus')"
            :dir="toast.dir || 'ltr'"
            :style="'pointer-events:auto;' + (toast.opacity < 1 ? 'opacity:' + toast.opacity + ';' : '') + 'cursor:default;' + (toast.enter_animation && toast.enter_animation !== 'none' ? 'animation:toast-enter-' + toast.enter_animation + ' ' + (toast.enter_duration || 0.5) + 's ease forwards;' : '')"
            class="rounded-xl overflow-hidden shadow-lg shadow-black/5 ring-1 ring-black/5 dark:shadow-black/40 dark:ring-white/10"
            :class="{
                'border': toast.show_border !== false,
                'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200': toast.type === 'success',
                'border-green-200 dark:border-green-800': toast.type === 'success' && toast.show_border !== false,
                'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200': toast.type === 'error',
                'border-red-200 dark:border-red-800': toast.type === 'error' && toast.show_border !== false,
                'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-200': toast.type === 'warning',
                'border-amber-200 dark:border-amber-800': toast.type === 'warning' && toast.show_border !== false,
                'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200': toast.type === 'info',
                'border-blue-200 dark:border-blue-800': toast.type === 'info' && toast.show_border !== false
            }"
            role="alert"
            :aria-live="toast.type === 'error' ? 'assertive' : 'polite'"
            aria-atomic="true"
        >
            <template x-if="toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position === 'top'">
                <div class="h-1 w-full" :class="{ 'bg-green-200 dark:bg-green-900': toast.type === 'success', 'bg-red-200 dark:bg-red-900': toast.type === 'error', 'bg-amber-200 dark:bg-amber-900': toast.type === 'warning', 'bg-blue-200 dark:bg-blue-900': toast.type === 'info' }">
                    <div class="h-full transition-none" :class="{ 'bg-green-500 dark:bg-green-400': toast.type === 'success', 'bg-red-500 dark:bg-red-400': toast.type === 'error', 'bg-amber-500 dark:bg-amber-400': toast.type === 'warning', 'bg-blue-500 dark:bg-blue-400': toast.type === 'info' }" :style="'width:' + (progress[toast.id] ?? 100) + '%;' + (toast.progress_direction === 'rtl' ? 'margin-left:auto;' : '')"></div>
                </div>
            </template>
            <div class="p-4 flex items-start gap-3">
                <template x-if="toast.show_icon !== false">
                    <div class="shrink-0 mt-0.5">
                        <template x-if="toast.custom_icon"><span x-html="toast.custom_icon"></span></template>
                        <template x-if="!toast.custom_icon && toast.type === 'success'"><svg class="h-5 w-5 text-green-500 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></template>
                        <template x-if="!toast.custom_icon && toast.type === 'error'"><svg class="h-5 w-5 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></template>
                        <template x-if="!toast.custom_icon && toast.type === 'warning'"><svg class="h-5 w-5 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg></template>
                        <template x-if="!toast.custom_icon && toast.type === 'info'"><svg class="h-5 w-5 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></template>
                    </div>
                </template>
                <div class="flex-1 min-w-0" style="cursor:default;">
                    <p x-show="toast.title" x-cloak x-text="toast.title" class="text-sm font-semibold tracking-tight"></p>
                    <p x-text="toast.message" class="text-sm leading-relaxed break-words"></p>
                </div>
                <template x-if="toast.show_close !== false">
                    <button type="button" @click="dismiss(toast.id)" class="shrink-0 rounded-md p-1 opacity-60 hover:opacity-100 transition-opacity cursor-pointer focus:outline-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-transparent" :class="{ 'focus-visible:ring-green-500': toast.type === 'success', 'focus-visible:ring-red-500': toast.type === 'error', 'focus-visible:ring-amber-500': toast.type === 'warning', 'focus-visible:ring-blue-500': toast.type === 'info' }" aria-label="{{ __('toast::toast.dismiss') }}">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </template>
            </div>
            <template x-if="toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position !== 'top'">
                <div class="h-1 w-full" :class="{ 'bg-green-200 dark:bg-green-900': toast.type === 'success', 'bg-red-200 dark:bg-red-900': toast.type === 'error', 'bg-amber-200 dark:bg-amber-900': toast.type === 'warning', 'bg-blue-200 dark:bg-blue-900': toast.type === 'info' }">
                    <div class="h-full transition-none" :class="{ 'bg-green-500 dark:bg-green-400': toast.type === 'success', 'bg-red-500 dark:bg-red-400': toast.type === 'error', 'bg-amber-500 dark:bg-amber-400': toast.type === 'warning', 'bg-blue-500 dark:bg-blue-400': toast.type === 'info' }" :style="'width:' + (progress[toast.id] ?? 100) + '%;' + (toast.progress_direction === 'rtl' ? 'margin-left:auto;' : '')"></div>
                </div>
            </template>
        </div>
    </template>
</div>
<script>
function toastContainer_{{ str_replace(['-', ' '], '_', $pos) }}() {
    return {
        toasts: {!! Js::from(array_values($posToasts)) !!},
        progress: {},
        timers: {},
        paused: {},
        holders: {},
        exiting: {},
        reduceMotion() {
            return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        },
        dismiss(id) {
            var toast = this.toasts.find(function(t) { return t.id === id; });
            if (!toast || this.exiting[id]) return;
            if (this.timers[id]) cancelAnimationFrame(this.timers[id]);
            delete this.timers[id];
            delete this.paused[id];
            delete this.holders[id];
            var anim = toast.exit_animation || 'none';
            var dur = toast.exit_duration || 0.5;
            if (anim === 'none' || this.reduceMotion()) { this.remove(id); return; }
            this.exiting[id] = true;
            // Scoped to this container so a second position is left alone.
            var el = this.$el.querySelector('[data-toast-id="' + id + '"]');
            if (el) {
                el.style.animation = 'toast-' + anim + ' ' + dur + 's ease forwards';
                var self = this;
                setTimeout(function() { self.remove(id); }, dur * 1000);
            } else { this.remove(id); }
        },
        remove(id) {
            delete this.progress[id];
            delete this.exiting[id];
            this.toasts = this.toasts.filter(function(t) { return t.id !== id; });
        },
        hold(id, source) {
            this.holders[id] = this.holders[id] || {};
            this.holders[id][source] = true;
            if (!this.timers[id]) return;
            this.paused[id] = true;
            cancelAnimationFrame(this.timers[id]);
            delete this.timers[id];
        },
        release(id, source) {
            if (this.holders[id]) delete this.holders[id][source];
            if (this.holders[id] && Object.keys(this.holders[id]).length) return;
            var toast = this.toasts.find(function(t) { return t.id === id; });
            if (!toast || !this.paused[id]) return;
            delete this.paused[id];
            var remaining = (this.progress[id] || 100) / 100 * toast.duration;
            this.startTimerFrom(toast, remaining);
        },
        startTimerFrom(toast, remaining) {
            if (!toast.auto_dismiss || remaining <= 0) return;
            var id = toast.id;
            var startPct = this.progress[id] || 100;
            var start = Date.now();
            var self = this;
            function tick() {
                if (self.paused[id]) return;
                var elapsed = Date.now() - start;
                var pct = Math.max(0, startPct - (elapsed / remaining * startPct));
                self.progress[id] = pct;
                if (pct <= 0) { self.dismiss(id); } else { self.timers[id] = requestAnimationFrame(tick); }
            }
            this.timers[id] = requestAnimationFrame(tick);
        },
        startTimer(toast) {
            if (!toast.auto_dismiss || toast.duration <= 0) return;
            this.progress[toast.id] = 100;
            this.startTimerFrom(toast, toast.duration);
        },
        init() {
            var self = this;
            this.toasts.forEach(function(t) { self.startTimer(t); });
        },
        destroy() {
            var self = this;
            Object.keys(this.timers).forEach(function(k) { cancelAnimationFrame(self.timers[k]); });
        }
    };
}
</script>
@endforeach
@endif
