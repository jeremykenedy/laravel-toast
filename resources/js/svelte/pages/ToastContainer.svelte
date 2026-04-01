<script>
    import { __ } from "@/i18n/translator"
    import { onMount, onDestroy } from 'svelte'

    export let initialToasts = []
    export let position = 'top-right'

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

    function getStyle(toast) { return styles[toast.type] || styles.info }

    function dismiss(id) {
        const toast = toasts.find(t => t.id === id)
        if (timers[id]) { cancelAnimationFrame(timers[id]); delete timers[id] }
        delete pausedMap[id]
        const anim = toast?.exit_animation || 'none'
        const dur = toast?.exit_duration || 0.5
        if (anim !== 'none') {
            const el = document.querySelector(`[data-toast-id="${id}"]`)
            if (el) { el.style.animation = `toast-${anim} ${dur}s ease forwards` }
            setTimeout(() => remove(id), dur * 1000)
        } else remove(id)
    }

    function remove(id) { delete progress[id]; toasts = toasts.filter(t => t.id !== id) }

    function startTimer(toast) {
        if (!toast.auto_dismiss || toast.duration <= 0) return
        progress[toast.id] = 100
        const start = Date.now(), id = toast.id, dur = toast.duration
        function tick() {
            if (pausedMap[id]) return
            const elapsed = Date.now() - start
            progress[id] = Math.max(0, 100 - (elapsed / dur * 100))
            progress = progress
            if (progress[id] <= 0) dismiss(id); else timers[id] = requestAnimationFrame(tick)
        }
        timers[id] = requestAnimationFrame(tick)
    }

    function pauseTimer(id) { if (!timers[id]) return; pausedMap[id] = true; cancelAnimationFrame(timers[id]); delete timers[id] }
    function resumeTimer(id) { const t = toasts.find(x => x.id === id); if (!t || !pausedMap[id]) return; delete pausedMap[id]; startTimer(t) }

    function enterStyle(toast) {
        const anim = toast.enter_animation || 'none'
        return anim !== 'none' ? `animation:toast-enter-${anim} ${toast.enter_duration || 0.5}s ease forwards;` : ''
    }

    $: posStyle = positionMap[position] || positionMap['top-right']

    onMount(() => {
        toasts = initialToasts.length ? [...initialToasts] : [...(window.__toasts || [])]
        toasts.forEach(startTimer)
    })

    onDestroy(() => { Object.values(timers).forEach(id => cancelAnimationFrame(id)) })
</script>

{#if toasts.length}
<div style="position:fixed;{posStyle}z-index:9999;width:24rem;max-width:calc(100vw - 2rem);display:flex;flex-direction:column;gap:0.75rem;" role="status" aria-live="polite">
    {#each toasts as toast (toast.id)}
        {@const ts = getStyle(toast)}
        <div data-toast-id={toast.id} dir={toast.dir || 'ltr'}
             style="cursor:default;{toast.opacity < 1 ? 'opacity:'+toast.opacity+';' : ''}{enterStyle(toast)}"
             on:mouseenter={() => toast.pause_on_hover && pauseTimer(toast.id)}
             on:mouseleave={() => toast.pause_on_hover && resumeTimer(toast.id)}
             class="rounded-lg shadow-lg overflow-hidden {ts.bg} {toast.show_border !== false ? 'border ' + ts.border : ''}"
             role="alert">
            {#if toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position === 'top'}
            <div class="h-1 w-full {ts.barBg}"><div class="h-full {ts.bar}" style="width:{progress[toast.id] ?? 100}%;transition:none;{toast.progress_direction === 'rtl' ? 'margin-left:auto;' : ''}"></div></div>
            {/if}
            <div class="p-4 flex items-start gap-3">
                {#if toast.show_icon !== false}
                <div class="flex-shrink-0 mt-0.5">
                    {#if toast.custom_icon}
                        {@html toast.custom_icon}
                    {:else}
                        <svg class="h-5 w-5 {ts.icon}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d={iconPaths[toast.type] || iconPaths.info} /></svg>
                    {/if}
                </div>
                {/if}
                <div class="flex-1 min-w-0" style="cursor:default;">
                    {#if toast.title}<p class="text-sm font-semibold">{toast.title}</p>{/if}
                    <p class="text-sm">{toast.message}</p>
                </div>
                {#if toast.show_close !== false}
                <button on:click={() => dismiss(toast.id)} class="flex-shrink-0 rounded-md p-1 opacity-60 hover:opacity-100 transition-opacity cursor-pointer" aria-label="Dismiss">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
                {/if}
            </div>
            {#if toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position !== 'top'}
            <div class="h-1 w-full {ts.barBg}"><div class="h-full {ts.bar}" style="width:{progress[toast.id] ?? 100}%;transition:none;{toast.progress_direction === 'rtl' ? 'margin-left:auto;' : ''}"></div></div>
            {/if}
        </div>
    {/each}
</div>
{/if}
