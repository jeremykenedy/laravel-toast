<script setup>
import { __ } from "@/i18n/translator"
import { ref, reactive, onMounted, computed, onUnmounted } from 'vue'

const props = defineProps({
    initialToasts: { type: Array, default: () => [] },
    position: { type: String, default: 'top-right' },
})

const toasts = ref([])
const progress = reactive({})
const timers = reactive({})
const paused = reactive({})
const exiting = reactive({})

const positionMap = {
    'top-left': 'top:0.5rem;left:0.5rem;',
    'top-center': 'top:0.5rem;left:50%;transform:translateX(-50%);',
    'top-right': 'top:0.5rem;right:0.5rem;',
    'bottom-right': 'bottom:0.5rem;right:0.5rem;',
    'bottom-left': 'bottom:0.5rem;left:0.5rem;',
    'bottom-center': 'bottom:0.5rem;left:50%;transform:translateX(-50%);',
}

const positionStyle = computed(() => positionMap[props.position] || positionMap['top-right'])

const typeStyles = {
    success: { bg: 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200', border: 'border-green-200 dark:border-green-800', icon: 'text-green-500 dark:text-green-400', bar: 'bg-green-500 dark:bg-green-400', barBg: 'bg-green-200 dark:bg-green-900' },
    error: { bg: 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200', border: 'border-red-200 dark:border-red-800', icon: 'text-red-500 dark:text-red-400', bar: 'bg-red-500 dark:bg-red-400', barBg: 'bg-red-200 dark:bg-red-900' },
    warning: { bg: 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-200', border: 'border-amber-200 dark:border-amber-800', icon: 'text-amber-500 dark:text-amber-400', bar: 'bg-amber-500 dark:bg-amber-400', barBg: 'bg-amber-200 dark:bg-amber-900' },
    info: { bg: 'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200', border: 'border-blue-200 dark:border-blue-800', icon: 'text-blue-500 dark:text-blue-400', bar: 'bg-blue-500 dark:bg-blue-400', barBg: 'bg-blue-200 dark:bg-blue-900' },
}

function getStyle(toast) { return typeStyles[toast.type] || typeStyles.info }

function dismiss(id) {
    const toast = toasts.value.find(t => t.id === id)
    if (!toast || exiting[id]) return
    if (timers[id]) { cancelAnimationFrame(timers[id]); delete timers[id] }
    delete paused[id]
    const anim = toast.exit_animation || 'none'
    const dur = toast.exit_duration || 0.5
    if (anim === 'none') { remove(id); return }
    exiting[id] = true
    const el = document.querySelector(`[data-toast-id="${id}"]`)
    if (el) { el.style.animation = `toast-${anim} ${dur}s ease forwards`; setTimeout(() => remove(id), dur * 1000) }
    else remove(id)
}

function remove(id) { delete progress[id]; delete exiting[id]; toasts.value = toasts.value.filter(t => t.id !== id) }

function pauseTimer(id) { if (!timers[id]) return; paused[id] = true; cancelAnimationFrame(timers[id]); delete timers[id] }
function resumeTimer(id) { const t = toasts.value.find(x => x.id === id); if (!t || !paused[id]) return; delete paused[id]; startTimerFrom(t, (progress[id] ?? 100) / 100 * t.duration) }

function startTimerFrom(toast, remaining) {
    if (!toast.auto_dismiss || remaining <= 0) return
    const id = toast.id, startPct = progress[id] ?? 100, start = Date.now()
    function tick() {
        if (paused[id]) return
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
    return `animation:toast-enter-${anim} ${toast.enter_duration || 0.5}s ease forwards;`
}

onMounted(() => {
    const initial = props.initialToasts.length ? props.initialToasts : (window.__toasts || [])
    toasts.value = initial
    initial.forEach(startTimer)
})

onUnmounted(() => { Object.values(timers).forEach(id => cancelAnimationFrame(id)) })
</script>

<template>
    <div v-if="toasts.length" :style="'position:fixed;' + positionStyle + 'z-index:9999;width:24rem;max-width:calc(100vw - 2rem);display:flex;flex-direction:column;gap:0.75rem;'" role="status" aria-live="polite">
        <div v-for="toast in toasts" :key="toast.id" :data-toast-id="toast.id"
             :dir="toast.dir || 'ltr'"
             :style="(toast.opacity < 1 ? 'opacity:'+toast.opacity+';' : '') + 'cursor:default;' + enterStyle(toast)"
             @mouseenter="toast.pause_on_hover && pauseTimer(toast.id)"
             @mouseleave="toast.pause_on_hover && resumeTimer(toast.id)"
             :class="['rounded-lg shadow-lg overflow-hidden', getStyle(toast).bg, toast.show_border !== false ? 'border ' + getStyle(toast).border : '']"
             role="alert">
            <div v-if="toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position === 'top'" :class="['h-1 w-full', getStyle(toast).barBg]">
                <div :class="['h-full', getStyle(toast).bar]" :style="'width:'+(progress[toast.id]??100)+'%;transition:none;'+(toast.progress_direction==='rtl'?'margin-left:auto;':'')"></div>
            </div>
            <div class="p-4 flex items-start gap-3">
                <div v-if="toast.show_icon !== false" class="flex-shrink-0 mt-0.5">
                    <span v-if="toast.custom_icon" v-html="toast.custom_icon"></span>
                    <svg v-else-if="toast.type==='success'" :class="['h-5 w-5', getStyle(toast).icon]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg v-else-if="toast.type==='error'" :class="['h-5 w-5', getStyle(toast).icon]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg v-else-if="toast.type==='warning'" :class="['h-5 w-5', getStyle(toast).icon]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <svg v-else :class="['h-5 w-5', getStyle(toast).icon]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="flex-1 min-w-0" style="cursor:default;">
                    <p v-if="toast.title" class="text-sm font-semibold">{{ toast.title }}</p>
                    <p class="text-sm">{{ toast.message }}</p>
                </div>
                <button v-if="toast.show_close !== false" @click="dismiss(toast.id)" class="flex-shrink-0 rounded-md p-1 opacity-60 hover:opacity-100 transition-opacity cursor-pointer" aria-label="Dismiss">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </div>
            <div v-if="toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position !== 'top'" :class="['h-1 w-full', getStyle(toast).barBg]">
                <div :class="['h-full', getStyle(toast).bar]" :style="'width:'+(progress[toast.id]??100)+'%;transition:none;'+(toast.progress_direction==='rtl'?'margin-left:auto;':'')"></div>
            </div>
        </div>
    </div>
</template>
