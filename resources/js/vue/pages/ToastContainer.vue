<script setup>
import { ref, reactive, onMounted, computed, onUnmounted, watch } from 'vue'
import '../../../css/toast-animations.css'
import '../../../css/toast-themes.css'
import '../../../css/toast-components.css'
import { appendToast, bootstrapStyle, toastFramework, listenForToasts } from '../../toast-options.js'

const props = defineProps({
    initialToasts: { type: Array, default: () => [] },
    position: { type: String, default: 'top-right' },
    stack: { type: Boolean, default: undefined },
    cssFramework: { type: String, default: undefined },
    echo: { type: Object, default: undefined },
    channel: { type: String, default: null },
    dismissLabel: { type: String, default: 'Dismiss' },
})

function prefersReducedMotion() {
    return typeof window !== 'undefined' && window.matchMedia
        ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
        : false
}

const toasts = ref([])
const progress = reactive({})
const timers = reactive({})
const paused = reactive({})
const holders = reactive({})
const exiting = reactive({})
const exitTimers = {}
const seen = new Set()
let mounted = false
let unsubscribe = () => {}

const positionMap = {
    'top-left': 'top:0.5rem;left:0.5rem;',
    'top-center': 'top:0.5rem;left:50%;transform:translateX(-50%);',
    'top-right': 'top:0.5rem;right:0.5rem;',
    'bottom-right': 'bottom:0.5rem;right:0.5rem;',
    'bottom-left': 'bottom:0.5rem;left:0.5rem;',
    'bottom-center': 'bottom:0.5rem;left:50%;transform:translateX(-50%);',
}

const grouped = computed(() => {
    const groups = {}

    for (const toast of toasts.value) {
        const key = positionMap[toast.position] ? toast.position : props.position
        const at = positionMap[key] ? key : 'top-right'
        ;(groups[at] = groups[at] || []).push(toast)
    }


    return groups
})

function positionStyleFor(key) {
    return positionMap[key] || positionMap['top-right']
}

const typeStyles = {
    success: { bg: 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200', border: 'border-green-200 dark:border-green-800', icon: 'text-green-500 dark:text-green-400', bar: 'bg-green-500 dark:bg-green-400', barBg: 'bg-green-200 dark:bg-green-900' },
    error: { bg: 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200', border: 'border-red-200 dark:border-red-800', icon: 'text-red-500 dark:text-red-400', bar: 'bg-red-500 dark:bg-red-400', barBg: 'bg-red-200 dark:bg-red-900' },
    warning: { bg: 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-200', border: 'border-amber-200 dark:border-amber-800', icon: 'text-amber-500 dark:text-amber-400', bar: 'bg-amber-500 dark:bg-amber-400', barBg: 'bg-amber-200 dark:bg-amber-900' },
    info: { bg: 'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200', border: 'border-blue-200 dark:border-blue-800', icon: 'text-blue-500 dark:text-blue-400', bar: 'bg-blue-500 dark:bg-blue-400', barBg: 'bg-blue-200 dark:bg-blue-900' },
}

function getStyle(toast) { return bootstrapStyle(toast, props.cssFramework) || typeStyles[toast.type] || typeStyles.info }

function dismiss(id) {
    const toast = toasts.value.find(t => t.id === id)
    if (!toast || exiting[id]) return
    if (timers[id]) { cancelAnimationFrame(timers[id]); delete timers[id] }
    delete paused[id]
    const anim = toast.exit_animation || 'none'
    const dur = toast.exit_duration ?? 0.5
    if (anim === 'none' || prefersReducedMotion()) { remove(id); return }
    exiting[id] = true
    const el = document.querySelector(`[data-toast-id="${id}"]`)
    if (el) { el.style.animation = `toast-${anim} ${dur}s ease forwards`; exitTimers[id] = setTimeout(() => remove(id), dur * 1000) }
    else remove(id)
}

function remove(id) {
    cancelAnimationFrame(timers[id])
    clearTimeout(exitTimers[id])
    delete timers[id]; delete exitTimers[id]; delete paused[id]
    delete progress[id]; delete exiting[id]; delete holders[id]
    toasts.value = toasts.value.filter(t => t.id !== id)
}

function receive(incoming) {
    for (const toast of incoming || []) {
        if (!toast?.id || seen.has(toast.id)) continue
        seen.add(toast.id)
        const next = appendToast(toasts.value, toast, props.stack)
        for (const previous of toasts.value) {
            if (!next.some(item => item.id === previous.id)) remove(previous.id)
        }
        toasts.value = next
        startTimer(toast)
    }
}

function connect() {
    unsubscribe()
    unsubscribe = listenForToasts(props.echo || window.Echo, props.channel, toast => receive([toast]))
}

// Hover and focus are tracked apart so leaving one does not restart the
// countdown while the other is still holding it.
function hold(id, source) {
    holders[id] = { ...holders[id], [source]: true }
    if (!timers[id]) return
    paused[id] = true
    cancelAnimationFrame(timers[id])
    delete timers[id]
}

function release(id, source) {
    if (holders[id]) delete holders[id][source]
    if (holders[id] && Object.keys(holders[id]).length) return
    const toast = toasts.value.find(t => t.id === id)
    if (!toast || !paused[id]) return
    delete paused[id]
    startTimerFrom(toast, (progress[id] ?? 100) / 100 * toast.duration)
}

function startTimerFrom(toast, remaining) {
    if (!toast.auto_dismiss || remaining <= 0) return
    const id = toast.id, startPct = progress[id] ?? 100, start = Date.now()
    function tick() {
        if (paused[id] || exiting[id]) return
        const elapsed = Date.now() - start, pct = Math.max(0, startPct - (elapsed / remaining * startPct))
        progress[id] = pct
        if (pct <= 0) dismiss(id); else timers[id] = requestAnimationFrame(tick)
    }
    timers[id] = requestAnimationFrame(tick)
}

function startTimer(toast) { if (!toast.auto_dismiss || toast.duration <= 0) return; progress[toast.id] = 100; startTimerFrom(toast, toast.duration) }

function enterStyle(toast) {
    const anim = toast.enter_animation || 'none'
    if (anim === 'none') return ''
    return `animation:toast-enter-${anim} ${toast.enter_duration ?? 0.5}s ease forwards;`
}

onMounted(() => {
    const initial = props.initialToasts.length ? props.initialToasts : (window.__toasts || [])
    mounted = true
    receive(initial)
    connect()
})

watch(() => props.initialToasts, incoming => { if (mounted) receive(incoming) }, { deep: true })
watch(() => [props.echo, props.channel], () => { if (mounted) connect() })
onUnmounted(() => {
    mounted = false
    unsubscribe()
    Object.values(timers).forEach(id => cancelAnimationFrame(id))
    Object.values(exitTimers).forEach(id => clearTimeout(id))
})
</script>

<template>
    <div v-for="(group, at) in grouped" :key="at" :style="'position:fixed;' + positionStyleFor(at) + 'z-index:9999;width:24rem;max-width:calc(100vw - 1rem);display:flex;flex-direction:column;gap:0.75rem;pointer-events:none;'" role="status" aria-live="polite" aria-atomic="false">
        <div v-for="toast in group" :key="toast.id" :data-toast-id="toast.id" data-laravel-toast="component" :data-css-framework="toastFramework(toast, cssFramework)"
             :dir="toast.dir || 'ltr'"
             :style="'pointer-events:auto;' + (toast.show_border === false ? 'border:0;' : '') + (toast.opacity < 1 ? 'opacity:'+toast.opacity+';' : '') + 'cursor:default;' + enterStyle(toast)"
             @mouseenter="toast.pause_on_hover && hold(toast.id, 'hover')"
             @mouseleave="toast.pause_on_hover && release(toast.id, 'hover')"
             @focusin="toast.pause_on_hover && hold(toast.id, 'focus')"
             @focusout="toast.pause_on_hover && release(toast.id, 'focus')"
             :class="['laravel-toast-item', getStyle(toast).bg, toast.show_border !== false ? 'border ' + getStyle(toast).border : '']"
             role="alert"
             :aria-live="toast.type === 'error' ? 'assertive' : 'polite'"
             aria-atomic="true">
            <div v-if="toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position === 'top'" :class="['laravel-toast-progress', getStyle(toast).barBg]">
                <div :class="[getStyle(toast).bar]" :style="'width:'+(progress[toast.id]??100)+'%;transition:none;'+(toast.progress_direction==='rtl'?'margin-left:auto;':'')"></div>
            </div>
            <div class="laravel-toast-body">
                <div v-if="toast.show_icon !== false" class="laravel-toast-icon">
                    <span v-if="toast.custom_icon" v-html="toast.custom_icon"></span>
                    <svg v-else-if="toast.type==='success'" :class="[getStyle(toast).icon]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg v-else-if="toast.type==='error'" :class="[getStyle(toast).icon]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg v-else-if="toast.type==='warning'" :class="[getStyle(toast).icon]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <svg v-else :class="[getStyle(toast).icon]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="laravel-toast-content" style="cursor:default;">
                    <p v-if="toast.title" class="laravel-toast-title">{{ toast.title }}</p>
                    <p class="laravel-toast-message">{{ toast.message }}</p>
                </div>
                <button v-if="toast.show_close !== false" type="button" @click="dismiss(toast.id)" class="laravel-toast-close" :aria-label="dismissLabel">
                    <svg  fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </div>
            <div v-if="toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position !== 'top'" :class="['laravel-toast-progress', getStyle(toast).barBg]">
                <div :class="[getStyle(toast).bar]" :style="'width:'+(progress[toast.id]??100)+'%;transition:none;'+(toast.progress_direction==='rtl'?'margin-left:auto;':'')"></div>
            </div>
        </div>
    </div>
</template>
