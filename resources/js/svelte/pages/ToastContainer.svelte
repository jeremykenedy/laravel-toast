<script>
    import { onMount, onDestroy } from 'svelte'
    import '../../../css/toast-animations.css'

    export let initialToasts = []
    export let position = 'top-right'
    export let stack = true
    export let dismissLabel = 'Dismiss'

    const reduceMotion = typeof window !== 'undefined' && window.matchMedia
        ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
        : false

    const positionMap = {
        'top-left': 'top:0.5rem;left:0.5rem;', 'top-center': 'top:0.5rem;left:50%;transform:translateX(-50%);',
        'top-right': 'top:0.5rem;right:0.5rem;', 'bottom-right': 'bottom:0.5rem;right:0.5rem;',
        'bottom-left': 'bottom:0.5rem;left:0.5rem;', 'bottom-center': 'bottom:0.5rem;left:50%;transform:translateX(-50%);',
    }

    const styles = {
        success: { bg: 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200', border: 'border-green-200 dark:border-green-800', icon: 'text-green-500 dark:text-green-400', bar: 'bg-green-500 dark:bg-green-400', barBg: 'bg-green-200 dark:bg-green-900' },
        error: { bg: 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200', border: 'border-red-200 dark:border-red-800', icon: 'text-red-500 dark:text-red-400', bar: 'bg-red-500 dark:bg-red-400', barBg: 'bg-red-200 dark:bg-red-900' },
        warning: { bg: 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-200', border: 'border-amber-200 dark:border-amber-800', icon: 'text-amber-500 dark:text-amber-400', bar: 'bg-amber-500 dark:bg-amber-400', barBg: 'bg-amber-200 dark:bg-amber-900' },
        info: { bg: 'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200', border: 'border-blue-200 dark:border-blue-800', icon: 'text-blue-500 dark:text-blue-400', bar: 'bg-blue-500 dark:bg-blue-400', barBg: 'bg-blue-200 dark:bg-blue-900' },
    }
    const iconPaths = {
        success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        error: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    }

    let toasts = []
    let progress = {}
    let timers = {}
    let pausedMap = {}
    let holders = {}
    let exiting = {}

    function getStyle(toast) { return styles[toast.type] || styles.info }

    function dismiss(id) {
        const toast = toasts.find(t => t.id === id)
        if (!toast || exiting[id]) return
        if (timers[id]) { cancelAnimationFrame(timers[id]); delete timers[id] }
        delete pausedMap[id]
        const anim = toast.exit_animation || 'none'
        const dur = toast.exit_duration || 0.5
        if (anim === 'none' || reduceMotion) { remove(id); return }
        exiting[id] = true
        const el = document.querySelector(`[data-toast-id="${id}"]`)
        if (el) { el.style.animation = `toast-${anim} ${dur}s ease forwards` }
        setTimeout(() => remove(id), dur * 1000)
    }

    function remove(id) {
        delete progress[id]
        delete exiting[id]
        delete holders[id]
        toasts = toasts.filter(t => t.id !== id)
    }

    function startTimer(toast, remaining) {
        if (!toast.auto_dismiss || toast.duration <= 0 || remaining <= 0) return
        const id = toast.id, startPct = progress[id] ?? 100, start = Date.now()
        function tick() {
            if (pausedMap[id] || exiting[id]) return
            const elapsed = Date.now() - start
            progress[id] = Math.max(0, startPct - (elapsed / remaining * startPct))
            progress = progress
            if (progress[id] <= 0) dismiss(id); else timers[id] = requestAnimationFrame(tick)
        }
        timers[id] = requestAnimationFrame(tick)
    }

    // Hover and focus are tracked apart so leaving one does not restart the
    // countdown while the other is still holding it.
    function hold(id, source) {
        holders[id] = { ...holders[id], [source]: true }
        if (!timers[id]) return
        pausedMap[id] = true
        cancelAnimationFrame(timers[id])
        delete timers[id]
    }

    function release(id, source) {
        if (holders[id]) delete holders[id][source]
        if (holders[id] && Object.keys(holders[id]).length) return
        const toast = toasts.find(t => t.id === id)
        if (!toast || !pausedMap[id]) return
        delete pausedMap[id]
        startTimer(toast, (progress[id] ?? 100) / 100 * toast.duration)
    }

    function enterStyle(toast) {
        const anim = toast.enter_animation || 'none'
        return anim !== 'none' ? `animation:toast-enter-${anim} ${toast.enter_duration || 0.5}s ease forwards;` : ''
    }

    // Each toast may carry its own position, and stack: false shows only the
    // newest per position. The Blade and Livewire renderers already do this.
    $: grouped = groupByPosition(toasts, position, stack)

    function groupByPosition(list, fallback, keepAll) {
        const groups = {}

        for (const toast of list) {
            const key = positionMap[toast.position] ? toast.position : fallback
            const at = positionMap[key] ? key : 'top-right'
            ;(groups[at] = groups[at] || []).push(toast)
        }

        if (!keepAll) {
            for (const key of Object.keys(groups)) {
                groups[key] = groups[key].slice(-1)
            }
        }

        return Object.entries(groups)
    }

    function positionStyleFor(key) {
        return positionMap[key] || positionMap['top-right']
    }

    onMount(() => {
        toasts = initialToasts.length ? [...initialToasts] : [...(window.__toasts || [])]
        toasts.forEach(toast => {
            progress[toast.id] = 100
            startTimer(toast, toast.duration)
        })
        progress = progress
    })

    onDestroy(() => { Object.values(timers).forEach(id => cancelAnimationFrame(id)) })
</script>

{#each grouped as [at, group] (at)}
<div style="position:fixed;{positionStyleFor(at)}z-index:9999;width:24rem;max-width:calc(100vw - 1rem);display:flex;flex-direction:column;gap:0.75rem;pointer-events:none;" role="status" aria-live="polite" aria-atomic="false">
    {#each group as toast (toast.id)}
        {@const ts = getStyle(toast)}
        <div data-toast-id={toast.id} dir={toast.dir || 'ltr'}
             style="pointer-events:auto;cursor:default;{toast.opacity < 1 ? 'opacity:'+toast.opacity+';' : ''}{enterStyle(toast)}"
             on:mouseenter={() => toast.pause_on_hover && hold(toast.id, 'hover')}
             on:mouseleave={() => toast.pause_on_hover && release(toast.id, 'hover')}
             on:focusin={() => toast.pause_on_hover && hold(toast.id, 'focus')}
             on:focusout={() => toast.pause_on_hover && release(toast.id, 'focus')}
             class="rounded-xl shadow-lg shadow-black/5 ring-1 ring-black/5 dark:shadow-black/40 dark:ring-white/10 overflow-hidden {ts.bg} {toast.show_border !== false ? 'border ' + ts.border : ''}"
             role="alert"
             aria-live={toast.type === 'error' ? 'assertive' : 'polite'}
             aria-atomic="true">
            {#if toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position === 'top'}
            <div class="h-1 w-full {ts.barBg}"><div class="h-full {ts.bar}" style="width:{progress[toast.id] ?? 100}%;transition:none;{toast.progress_direction === 'rtl' ? 'margin-left:auto;' : ''}"></div></div>
            {/if}
            <div class="p-4 flex items-start gap-3">
                {#if toast.show_icon !== false}
                <div class="shrink-0 mt-0.5">
                    {#if toast.custom_icon}
                        {@html toast.custom_icon}
                    {:else}
                        <svg class="h-5 w-5 {ts.icon}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d={iconPaths[toast.type] || iconPaths.info} /></svg>
                    {/if}
                </div>
                {/if}
                <div class="flex-1 min-w-0" style="cursor:default;">
                    {#if toast.title}<p class="text-sm font-semibold tracking-tight">{toast.title}</p>{/if}
                    <p class="text-sm leading-relaxed break-words">{toast.message}</p>
                </div>
                {#if toast.show_close !== false}
                <button type="button" on:click={() => dismiss(toast.id)} class="shrink-0 rounded-md p-1 opacity-60 hover:opacity-100 transition-opacity cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-transparent" aria-label={dismissLabel}>
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
                {/if}
            </div>
            {#if toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position !== 'top'}
            <div class="h-1 w-full {ts.barBg}"><div class="h-full {ts.bar}" style="width:{progress[toast.id] ?? 100}%;transition:none;{toast.progress_direction === 'rtl' ? 'margin-left:auto;' : ''}"></div></div>
            {/if}
        </div>
    {/each}
</div>
{/each}
