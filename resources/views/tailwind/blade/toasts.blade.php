@php
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
<style>{!! "
@keyframes toast-slide-left{0%{transform:translateX(0);opacity:1}100%{transform:translateX(-120%);opacity:0}}
@keyframes toast-slide-right{0%{transform:translateX(0);opacity:1}100%{transform:translateX(120%);opacity:0}}
@keyframes toast-slide-top{0%{transform:translateY(0);opacity:1}100%{transform:translateY(-120%);opacity:0}}
@keyframes toast-slide-bottom{0%{transform:translateY(0);opacity:1}100%{transform:translateY(120%);opacity:0}}
@keyframes toast-bounce-left{0%{transform:translateX(0);opacity:1}30%{transform:translateX(8%)}100%{transform:translateX(-120%);opacity:0}}
@keyframes toast-bounce-right{0%{transform:translateX(0);opacity:1}30%{transform:translateX(-8%)}100%{transform:translateX(120%);opacity:0}}
@keyframes toast-bounce-top{0%{transform:translateY(0);opacity:1}30%{transform:translateY(15%)}100%{transform:translateY(-120%);opacity:0}}
@keyframes toast-bounce-bottom{0%{transform:translateY(0);opacity:1}30%{transform:translateY(-15%)}100%{transform:translateY(120%);opacity:0}}
@keyframes toast-fade{0%{opacity:1}100%{opacity:0}}
@keyframes toast-shrink-left{0%{transform:scaleX(1);transform-origin:right;opacity:1}100%{transform:scaleX(0);transform-origin:right;opacity:0}}
@keyframes toast-shrink-right{0%{transform:scaleX(1);transform-origin:left;opacity:1}100%{transform:scaleX(0);transform-origin:left;opacity:0}}
@keyframes toast-shrink-top{0%{transform:scaleY(1);transform-origin:bottom;opacity:1}100%{transform:scaleY(0);transform-origin:bottom;opacity:0}}
@keyframes toast-shrink-bottom{0%{transform:scaleY(1);transform-origin:top;opacity:1}100%{transform:scaleY(0);transform-origin:top;opacity:0}}
@keyframes toast-enter-slide-left{0%{transform:translateX(-120%);opacity:0}100%{transform:translateX(0);opacity:1}}
@keyframes toast-enter-slide-right{0%{transform:translateX(120%);opacity:0}100%{transform:translateX(0);opacity:1}}
@keyframes toast-enter-slide-top{0%{transform:translateY(-120%);opacity:0}100%{transform:translateY(0);opacity:1}}
@keyframes toast-enter-slide-bottom{0%{transform:translateY(120%);opacity:0}100%{transform:translateY(0);opacity:1}}
@keyframes toast-enter-bounce-left{0%{transform:translateX(-120%);opacity:0}70%{transform:translateX(5%);opacity:1}100%{transform:translateX(0)}}
@keyframes toast-enter-bounce-right{0%{transform:translateX(120%);opacity:0}70%{transform:translateX(-5%);opacity:1}100%{transform:translateX(0)}}
@keyframes toast-enter-bounce-top{0%{transform:translateY(-120%);opacity:0}70%{transform:translateY(10%);opacity:1}100%{transform:translateY(0)}}
@keyframes toast-enter-bounce-bottom{0%{transform:translateY(120%);opacity:0}70%{transform:translateY(-10%);opacity:1}100%{transform:translateY(0)}}
@keyframes toast-enter-fade{0%{opacity:0}100%{opacity:1}}
@keyframes toast-enter-shrink-left{0%{transform:scaleX(0);transform-origin:right;opacity:0}100%{transform:scaleX(1);transform-origin:right;opacity:1}}
@keyframes toast-enter-shrink-right{0%{transform:scaleX(0);transform-origin:left;opacity:0}100%{transform:scaleX(1);transform-origin:left;opacity:1}}
@keyframes toast-enter-shrink-top{0%{transform:scaleY(0);transform-origin:bottom;opacity:0}100%{transform:scaleY(1);transform-origin:bottom;opacity:1}}
@keyframes toast-enter-shrink-bottom{0%{transform:scaleY(0);transform-origin:top;opacity:0}100%{transform:scaleY(1);transform-origin:top;opacity:1}}
@keyframes toast-flip-left{0%{transform:perspective(600px) rotateY(0);opacity:1}100%{transform:perspective(600px) rotateY(-90deg);opacity:0}}
@keyframes toast-flip-right{0%{transform:perspective(600px) rotateY(0);opacity:1}100%{transform:perspective(600px) rotateY(90deg);opacity:0}}
@keyframes toast-flip-top{0%{transform:perspective(600px) rotateX(0);opacity:1}100%{transform:perspective(600px) rotateX(90deg);opacity:0}}
@keyframes toast-flip-bottom{0%{transform:perspective(600px) rotateX(0);opacity:1}100%{transform:perspective(600px) rotateX(-90deg);opacity:0}}
@keyframes toast-enter-flip-left{0%{transform:perspective(600px) rotateY(90deg);opacity:0}100%{transform:perspective(600px) rotateY(0);opacity:1}}
@keyframes toast-enter-flip-right{0%{transform:perspective(600px) rotateY(-90deg);opacity:0}100%{transform:perspective(600px) rotateY(0);opacity:1}}
@keyframes toast-enter-flip-top{0%{transform:perspective(600px) rotateX(-90deg);opacity:0}100%{transform:perspective(600px) rotateX(0);opacity:1}}
@keyframes toast-enter-flip-bottom{0%{transform:perspective(600px) rotateX(90deg);opacity:0}100%{transform:perspective(600px) rotateX(0);opacity:1}}
@keyframes toast-flip-center{0%{transform:perspective(600px) rotateY(0);opacity:1}100%{transform:perspective(600px) rotateY(180deg);opacity:0}}
@keyframes toast-enter-flip-center{0%{transform:perspective(600px) rotateY(180deg);opacity:0}100%{transform:perspective(600px) rotateY(0);opacity:1}}
@keyframes toast-spin-left{0%{transform:rotate(0) translateX(0);opacity:1}100%{transform:rotate(-360deg) translateX(-120%);opacity:0}}
@keyframes toast-spin-right{0%{transform:rotate(0) translateX(0);opacity:1}100%{transform:rotate(360deg) translateX(120%);opacity:0}}
@keyframes toast-spin-top{0%{transform:rotate(0) translateY(0);opacity:1}100%{transform:rotate(-360deg) translateY(-120%);opacity:0}}
@keyframes toast-spin-bottom{0%{transform:rotate(0) translateY(0);opacity:1}100%{transform:rotate(360deg) translateY(120%);opacity:0}}
@keyframes toast-spin-center{0%{transform:rotate(0) scale(1);opacity:1}100%{transform:rotate(360deg) scale(0);opacity:0}}
@keyframes toast-enter-spin-left{0%{transform:rotate(360deg) translateX(-120%);opacity:0}100%{transform:rotate(0) translateX(0);opacity:1}}
@keyframes toast-enter-spin-right{0%{transform:rotate(-360deg) translateX(120%);opacity:0}100%{transform:rotate(0) translateX(0);opacity:1}}
@keyframes toast-enter-spin-top{0%{transform:rotate(360deg) translateY(-120%);opacity:0}100%{transform:rotate(0) translateY(0);opacity:1}}
@keyframes toast-enter-spin-bottom{0%{transform:rotate(-360deg) translateY(120%);opacity:0}100%{transform:rotate(0) translateY(0);opacity:1}}
@keyframes toast-enter-spin-center{0%{transform:rotate(-360deg) scale(0);opacity:0}100%{transform:rotate(0) scale(1);opacity:1}}
@keyframes toast-grow-left{0%{transform:scale(1);transform-origin:right center;opacity:1}100%{transform:scale(0);transform-origin:right center;opacity:0}}
@keyframes toast-grow-right{0%{transform:scale(1);transform-origin:left center;opacity:1}100%{transform:scale(0);transform-origin:left center;opacity:0}}
@keyframes toast-grow-top{0%{transform:scale(1);transform-origin:center bottom;opacity:1}100%{transform:scale(0);transform-origin:center bottom;opacity:0}}
@keyframes toast-grow-bottom{0%{transform:scale(1);transform-origin:center top;opacity:1}100%{transform:scale(0);transform-origin:center top;opacity:0}}
@keyframes toast-grow-center{0%{transform:scale(1);opacity:1}100%{transform:scale(0);opacity:0}}
@keyframes toast-enter-grow-left{0%{transform:scale(0);transform-origin:right center;opacity:0}100%{transform:scale(1);transform-origin:right center;opacity:1}}
@keyframes toast-enter-grow-right{0%{transform:scale(0);transform-origin:left center;opacity:0}100%{transform:scale(1);transform-origin:left center;opacity:1}}
@keyframes toast-enter-grow-top{0%{transform:scale(0);transform-origin:center bottom;opacity:0}100%{transform:scale(1);transform-origin:center bottom;opacity:1}}
@keyframes toast-enter-grow-bottom{0%{transform:scale(0);transform-origin:center top;opacity:0}100%{transform:scale(1);transform-origin:center top;opacity:1}}
@keyframes toast-enter-grow-center{0%{transform:scale(0);opacity:0}100%{transform:scale(1);opacity:1}}
@keyframes toast-slam-left{0%{transform:scale(1);opacity:1}40%{transform:scale(1.15) translateX(5%);opacity:1}100%{transform:scale(0.5) translateX(-120%);opacity:0}}
@keyframes toast-slam-right{0%{transform:scale(1);opacity:1}40%{transform:scale(1.15) translateX(-5%);opacity:1}100%{transform:scale(0.5) translateX(120%);opacity:0}}
@keyframes toast-slam-top{0%{transform:scale(1);opacity:1}40%{transform:scale(1.15) translateY(5%);opacity:1}100%{transform:scale(0.5) translateY(-120%);opacity:0}}
@keyframes toast-slam-bottom{0%{transform:scale(1);opacity:1}40%{transform:scale(1.15) translateY(-5%);opacity:1}100%{transform:scale(0.5) translateY(120%);opacity:0}}
@keyframes toast-slam-center{0%{transform:scale(1);opacity:1}40%{transform:scale(1.15);opacity:1}100%{transform:scale(0);opacity:0}}
@keyframes toast-enter-slam-left{0%{transform:scale(0.5) translateX(-120%);opacity:0}60%{transform:scale(1.15) translateX(3%);opacity:1}100%{transform:scale(1) translateX(0);opacity:1}}
@keyframes toast-enter-slam-right{0%{transform:scale(0.5) translateX(120%);opacity:0}60%{transform:scale(1.15) translateX(-3%);opacity:1}100%{transform:scale(1) translateX(0);opacity:1}}
@keyframes toast-enter-slam-top{0%{transform:scale(0.5) translateY(-120%);opacity:0}60%{transform:scale(1.15) translateY(3%);opacity:1}100%{transform:scale(1) translateY(0);opacity:1}}
@keyframes toast-enter-slam-bottom{0%{transform:scale(0.5) translateY(120%);opacity:0}60%{transform:scale(1.15) translateY(-3%);opacity:1}100%{transform:scale(1) translateY(0);opacity:1}}
@keyframes toast-enter-slam-center{0%{transform:scale(0);opacity:0}60%{transform:scale(1.2);opacity:1}100%{transform:scale(1);opacity:1}}
@keyframes toast-bounce-center{0%{transform:scale(1);opacity:1}30%{transform:scale(1.1);opacity:1}100%{transform:scale(0);opacity:0}}
@keyframes toast-enter-bounce-center{0%{transform:scale(0);opacity:0}70%{transform:scale(1.08);opacity:1}100%{transform:scale(1);opacity:1}}
@keyframes toast-shrink-center{0%{transform:scale(1);opacity:1}100%{transform:scale(0);opacity:0}}
@keyframes toast-enter-shrink-center{0%{transform:scale(0);opacity:0}100%{transform:scale(1);opacity:1}}
@keyframes toast-fade-center{0%{opacity:1}100%{opacity:0}}
@keyframes toast-enter-fade-center{0%{opacity:0}100%{opacity:1}}
@keyframes toast-slide{0%{transform:translateX(0);opacity:1}100%{transform:translateX(120%);opacity:0}}
@keyframes toast-enter-slide{0%{transform:translateX(120%);opacity:0}100%{transform:translateX(0);opacity:1}}
@keyframes toast-bounce{0%{transform:scale(1);opacity:1}30%{transform:scale(1.1);opacity:1}100%{transform:scale(0);opacity:0}}
@keyframes toast-enter-bounce{0%{transform:scale(0);opacity:0}70%{transform:scale(1.08);opacity:1}100%{transform:scale(1);opacity:1}}
@keyframes toast-shrink{0%{transform:scale(1);opacity:1}100%{transform:scale(0);opacity:0}}
@keyframes toast-enter-shrink{0%{transform:scale(0);opacity:0}100%{transform:scale(1);opacity:1}}
@keyframes toast-flip{0%{transform:perspective(600px) rotateY(0);opacity:1}100%{transform:perspective(600px) rotateY(180deg);opacity:0}}
@keyframes toast-enter-flip{0%{transform:perspective(600px) rotateY(180deg);opacity:0}100%{transform:perspective(600px) rotateY(0);opacity:1}}
@keyframes toast-spin{0%{transform:rotate(0) scale(1);opacity:1}100%{transform:rotate(360deg) scale(0);opacity:0}}
@keyframes toast-enter-spin{0%{transform:rotate(-360deg) scale(0);opacity:0}100%{transform:rotate(0) scale(1);opacity:1}}
@keyframes toast-grow{0%{transform:scale(1);opacity:1}100%{transform:scale(0);opacity:0}}
@keyframes toast-enter-grow{0%{transform:scale(0);opacity:0}100%{transform:scale(1);opacity:1}}
@keyframes toast-slam{0%{transform:scale(1);opacity:1}40%{transform:scale(1.15);opacity:1}100%{transform:scale(0);opacity:0}}
@keyframes toast-enter-slam{0%{transform:scale(0);opacity:0}60%{transform:scale(1.2);opacity:1}100%{transform:scale(1);opacity:1}}
@keyframes toast-wobble{0%{transform:translateX(0);opacity:1}15%{transform:translateX(-6px) rotate(-3deg)}30%{transform:translateX(5px) rotate(2deg)}45%{transform:translateX(-4px) rotate(-1deg)}60%{transform:translateX(2px) rotate(0)}75%{transform:translateX(-1px)}85%{transform:translateX(0);opacity:1}100%{transform:translateX(0);opacity:0}}
@keyframes toast-wobble-left{0%{transform:translateX(0);opacity:1}25%{transform:translateX(8%)}100%{transform:translateX(-120%);opacity:0}}
@keyframes toast-wobble-right{0%{transform:translateX(0);opacity:1}25%{transform:translateX(-8%)}100%{transform:translateX(120%);opacity:0}}
@keyframes toast-wobble-top{0%{transform:translateY(0);opacity:1}25%{transform:translateY(10%)}100%{transform:translateY(-120%);opacity:0}}
@keyframes toast-wobble-bottom{0%{transform:translateY(0);opacity:1}25%{transform:translateY(-10%)}100%{transform:translateY(120%);opacity:0}}
@keyframes toast-wobble-center{0%{transform:scale(1);opacity:1}15%{transform:scale(1.05) rotate(-2deg)}30%{transform:scale(0.95) rotate(2deg)}45%{transform:scale(1.02) rotate(-1deg)}60%{transform:scale(1);opacity:1}100%{transform:scale(0);opacity:0}}
@keyframes toast-enter-wobble{0%{transform:translateX(0);opacity:0}1%{opacity:1}15%{transform:translateX(-6px) rotate(-3deg)}30%{transform:translateX(5px) rotate(2deg)}45%{transform:translateX(-4px) rotate(-1deg)}60%{transform:translateX(2px)}75%{transform:translateX(-1px)}100%{transform:translateX(0);opacity:1}}
@keyframes toast-enter-wobble-left{0%{transform:translateX(-120%);opacity:0}70%{transform:translateX(5%);opacity:1}85%{transform:translateX(-2%)}100%{transform:translateX(0)}}
@keyframes toast-enter-wobble-right{0%{transform:translateX(120%);opacity:0}70%{transform:translateX(-5%);opacity:1}85%{transform:translateX(2%)}100%{transform:translateX(0)}}
@keyframes toast-enter-wobble-top{0%{transform:translateY(-120%);opacity:0}70%{transform:translateY(8%);opacity:1}85%{transform:translateY(-3%)}100%{transform:translateY(0)}}
@keyframes toast-enter-wobble-bottom{0%{transform:translateY(120%);opacity:0}70%{transform:translateY(-8%);opacity:1}85%{transform:translateY(3%)}100%{transform:translateY(0)}}
@keyframes toast-enter-wobble-center{0%{transform:scale(0);opacity:0}50%{transform:scale(1.08);opacity:1}65%{transform:scale(0.95)}80%{transform:scale(1.02)}100%{transform:scale(1);opacity:1}}
" !!}</style>
@foreach($grouped as $pos => $posToasts)
@php $containerId = 'toast-container-' . str_replace(['-', ' '], '_', $pos); @endphp
<div
    id="{{ $containerId }}"
    x-data="toastContainer_{{ str_replace(['-', ' '], '_', $pos) }}()"
    style="position: fixed; {{ $positionMap[$pos] }} z-index: 9999; width: 24rem; max-width: calc(100vw - 2rem); display: flex; flex-direction: column; gap: 0.75rem;"
    role="status"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            :data-toast-id="toast.id"
            x-show="toasts.find(t => t.id === toast.id)"
            x-cloak
            @mouseenter="toast.pause_on_hover && pause(toast.id)"
            @mouseleave="toast.pause_on_hover && resume(toast.id)"
            :dir="toast.dir || 'ltr'"
            :style="(toast.opacity < 1 ? 'opacity:' + toast.opacity + ';' : '') + 'cursor:default;' + (toast.enter_animation && toast.enter_animation !== 'none' ? 'animation:toast-enter-' + toast.enter_animation + ' ' + (toast.enter_duration || 0.5) + 's ease forwards;' : '')"
            class="rounded-lg shadow-lg overflow-hidden"
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
        >
            <template x-if="toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position === 'top'">
                <div class="h-1 w-full" :class="{ 'bg-green-200 dark:bg-green-900': toast.type === 'success', 'bg-red-200 dark:bg-red-900': toast.type === 'error', 'bg-amber-200 dark:bg-amber-900': toast.type === 'warning', 'bg-blue-200 dark:bg-blue-900': toast.type === 'info' }">
                    <div class="h-full transition-none" :class="{ 'bg-green-500 dark:bg-green-400': toast.type === 'success', 'bg-red-500 dark:bg-red-400': toast.type === 'error', 'bg-amber-500 dark:bg-amber-400': toast.type === 'warning', 'bg-blue-500 dark:bg-blue-400': toast.type === 'info' }" :style="'width:' + (progress[toast.id] ?? 100) + '%;' + (toast.progress_direction === 'rtl' ? 'margin-left:auto;' : '')"></div>
                </div>
            </template>
            <div class="p-4 flex items-start gap-3">
                <template x-if="toast.show_icon !== false">
                    <div class="flex-shrink-0 mt-0.5">
                        <template x-if="toast.custom_icon"><span x-html="toast.custom_icon"></span></template>
                        <template x-if="!toast.custom_icon && toast.type === 'success'"><svg class="h-5 w-5 text-green-500 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></template>
                        <template x-if="!toast.custom_icon && toast.type === 'error'"><svg class="h-5 w-5 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></template>
                        <template x-if="!toast.custom_icon && toast.type === 'warning'"><svg class="h-5 w-5 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg></template>
                        <template x-if="!toast.custom_icon && toast.type === 'info'"><svg class="h-5 w-5 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></template>
                    </div>
                </template>
                <div class="flex-1 min-w-0" style="cursor:default;">
                    <p x-show="toast.title" x-text="toast.title" class="text-sm font-semibold"></p>
                    <p x-text="toast.message" class="text-sm"></p>
                </div>
                <template x-if="toast.show_close !== false">
                    <button @click="dismiss(toast.id)" class="flex-shrink-0 rounded-md p-1 opacity-60 hover:opacity-100 transition-opacity cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-1" :class="{ 'focus:ring-green-500': toast.type === 'success', 'focus:ring-red-500': toast.type === 'error', 'focus:ring-amber-500': toast.type === 'warning', 'focus:ring-blue-500': toast.type === 'info' }" aria-label="{{ __('toast::toast.dismiss') }}">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
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
        exiting: {},
        dismiss(id) {
            var toast = this.toasts.find(function(t) { return t.id === id; });
            if (!toast || this.exiting[id]) return;
            if (this.timers[id]) cancelAnimationFrame(this.timers[id]);
            delete this.timers[id];
            delete this.paused[id];
            var anim = toast.exit_animation || 'none';
            var dur = toast.exit_duration || 0.5;
            if (anim === 'none') { this.remove(id); return; }
            this.exiting[id] = true;
            var el = document.querySelector('[data-toast-id="' + id + '"]');
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
        pause(id) {
            if (!this.timers[id]) return;
            this.paused[id] = true;
            cancelAnimationFrame(this.timers[id]);
            delete this.timers[id];
        },
        resume(id) {
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
        }
    };
}
</script>
@endforeach
@endif
