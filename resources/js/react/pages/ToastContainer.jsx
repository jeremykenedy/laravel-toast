import React, { useState, useEffect, useCallback, useRef } from 'react'
import '../../../css/toast-animations.css'
import '../../../css/toast-themes.css'
import '../../../css/toast-components.css'
import { appendToast, bootstrapStyle, toastFramework, listenForToasts } from '../../toast-options.js'

const emptyToasts = []

const styles = {
    success: { bg: 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200', border: 'border-green-200 dark:border-green-800', icon: 'text-green-500 dark:text-green-400', bar: 'bg-green-500 dark:bg-green-400', barBg: 'bg-green-200 dark:bg-green-900' },
    error: { bg: 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200', border: 'border-red-200 dark:border-red-800', icon: 'text-red-500 dark:text-red-400', bar: 'bg-red-500 dark:bg-red-400', barBg: 'bg-red-200 dark:bg-red-900' },
    warning: { bg: 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-200', border: 'border-amber-200 dark:border-amber-800', icon: 'text-amber-500 dark:text-amber-400', bar: 'bg-amber-500 dark:bg-amber-400', barBg: 'bg-amber-200 dark:bg-amber-900' },
    info: { bg: 'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200', border: 'border-blue-200 dark:border-blue-800', icon: 'text-blue-500 dark:text-blue-400', bar: 'bg-blue-500 dark:bg-blue-400', barBg: 'bg-blue-200 dark:bg-blue-900' },
}

const positionMap = {
    'top-left': { top: '0.5rem', left: '0.5rem' },
    'top-center': { top: '0.5rem', left: '50%', transform: 'translateX(-50%)' },
    'top-right': { top: '0.5rem', right: '0.5rem' },
    'bottom-right': { bottom: '0.5rem', right: '0.5rem' },
    'bottom-left': { bottom: '0.5rem', left: '0.5rem' },
    'bottom-center': { bottom: '0.5rem', left: '50%', transform: 'translateX(-50%)' },
}

function prefersReducedMotion() {
    return typeof window !== 'undefined' && window.matchMedia
        ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
        : false
}

function ToastIcon({ type, className }) {
    const paths = {
        success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        error: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    }

    return <svg className={`${className}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d={paths[type] || paths.info} /></svg>
}

export default function ToastContainer({ initialToasts = emptyToasts, position = 'top-right', stack, cssFramework, echo, channel, dismissLabel = 'Dismiss' }) {
    const [toasts, setToasts] = useState([])
    // State drives the render, the refs let the animation frame read current
    // values without the callbacks going stale or rerunning the effect.
    const [progress, setProgress] = useState({})
    const toastsRef = useRef([])
    const progressRef = useRef({})
    const timers = useRef({})
    const paused = useRef({})
    const holders = useRef({})
    const exiting = useRef({})
    const exitTimers = useRef({})
    const seen = useRef(new Set())

    const writeToasts = useCallback((next) => {
        toastsRef.current = next
        setToasts(next)
    }, [])

    const writeProgress = useCallback((id, pct) => {
        progressRef.current = { ...progressRef.current, [id]: pct }
        setProgress(progressRef.current)
    }, [])

    const remove = useCallback((id) => {
        cancelAnimationFrame(timers.current[id])
        clearTimeout(exitTimers.current[id])
        delete exitTimers.current[id]
        delete timers.current[id]
        delete paused.current[id]
        delete holders.current[id]
        delete exiting.current[id]

        const next = { ...progressRef.current }
        delete next[id]
        progressRef.current = next
        setProgress(next)

        writeToasts(toastsRef.current.filter(t => t.id !== id))
    }, [writeToasts])

    const dismiss = useCallback((id) => {
        if (exiting.current[id]) return
        if (timers.current[id]) { cancelAnimationFrame(timers.current[id]); delete timers.current[id] }

        const toast = toastsRef.current.find(t => t.id === id)
        const anim = toast?.exit_animation || 'none'
        const dur = toast?.exit_duration ?? 0.5

        if (anim === 'none' || prefersReducedMotion()) { remove(id); return }

        exiting.current[id] = true
        const el = document.querySelector(`[data-toast-id="${id}"]`)
        if (el) { el.style.animation = `toast-${anim} ${dur}s ease forwards` }
        exitTimers.current[id] = setTimeout(() => remove(id), dur * 1000)
    }, [remove])

    const startTimer = useCallback((toast, remaining) => {
        if (!toast.auto_dismiss || toast.duration <= 0 || remaining <= 0) return

        const id = toast.id
        const startPct = remaining / toast.duration * 100
        const start = Date.now()

        const tick = () => {
            if (paused.current[id] || exiting.current[id]) return
            const elapsed = Date.now() - start
            const pct = Math.max(0, startPct - (elapsed / remaining * startPct))
            writeProgress(id, pct)
            if (pct <= 0) { dismiss(id) } else { timers.current[id] = requestAnimationFrame(tick) }
        }

        timers.current[id] = requestAnimationFrame(tick)
    }, [dismiss, writeProgress])

    // Hover and focus are tracked apart so leaving one does not restart the
    // countdown while the other is still holding it.
    const hold = useCallback((toast, source) => {
        if (!toast.pause_on_hover) return

        const id = toast.id
        holders.current[id] = { ...holders.current[id], [source]: true }
        if (!timers.current[id]) return
        paused.current[id] = true
        cancelAnimationFrame(timers.current[id])
        delete timers.current[id]
    }, [])

    const release = useCallback((toast, source) => {
        if (!toast.pause_on_hover) return

        const id = toast.id
        if (holders.current[id]) delete holders.current[id][source]
        if (holders.current[id] && Object.keys(holders.current[id]).length) return
        if (!paused.current[id]) return

        delete paused.current[id]
        startTimer(toast, (progressRef.current[id] ?? 100) / 100 * toast.duration)
    }, [startTimer])

    const receive = useCallback((incoming) => {
        for (const toast of incoming || []) {
            if (!toast?.id || seen.current.has(toast.id)) continue
            seen.current.add(toast.id)
            const next = appendToast(toastsRef.current, toast, stack)
            for (const previous of toastsRef.current) {
                if (!next.some(item => item.id === previous.id)) remove(previous.id)
            }
            writeToasts(next)
            writeProgress(toast.id, 100)
            startTimer(toast, toast.duration)
        }
    }, [stack, remove, writeToasts, writeProgress, startTimer])

    useEffect(() => {
        receive(initialToasts.length ? initialToasts : (window.__toasts || []))
        return () => {
            Object.values(timers.current).forEach(cancelAnimationFrame)
            Object.values(exitTimers.current).forEach(clearTimeout)
            timers.current = {}; exitTimers.current = {}; paused.current = {}; holders.current = {}; exiting.current = {}
            seen.current.clear()
            toastsRef.current = []
        }
    }, [])

    useEffect(() => { receive(initialToasts) }, [initialToasts, receive])
    useEffect(() => listenForToasts(echo || window.Echo, channel, toast => receive([toast])), [echo, channel, receive])

    if (!toasts.length) return null

    const s = (toast) => bootstrapStyle(toast, cssFramework) || styles[toast.type] || styles.info

    const grouped = {}
    for (const toast of toasts) {
        const key = positionMap[toast.position] ? toast.position : position
        const at = positionMap[key] ? key : 'top-right'
        ;(grouped[at] = grouped[at] || []).push(toast)
    }

    return Object.entries(grouped).map(([at, group]) => (
        <div
            key={at}
            style={{ position: 'fixed', zIndex: 9999, width: '24rem', maxWidth: 'calc(100vw - 1rem)', display: 'flex', flexDirection: 'column', gap: '0.75rem', pointerEvents: 'none', ...positionMap[at] }}
            role="status"
            aria-live="polite"
            aria-atomic="false"
        >
            {group.map(toast => {
                const ts = s(toast)
                const enterStyle = (toast.enter_animation && toast.enter_animation !== 'none')
                    ? { animation: `toast-enter-${toast.enter_animation} ${toast.enter_duration ?? 0.5}s ease forwards` }
                    : {}

                return (
                    <div key={toast.id} data-toast-id={toast.id} data-laravel-toast="component" data-css-framework={toastFramework(toast, cssFramework)} dir={toast.dir || 'ltr'}
                         style={{ cursor: 'default', pointerEvents: 'auto', ...(toast.show_border === false ? { border: 0 } : {}), ...(toast.opacity < 1 ? { opacity: toast.opacity } : {}), ...enterStyle }}
                         onMouseEnter={() => hold(toast, 'hover')}
                         onMouseLeave={() => release(toast, 'hover')}
                         onFocus={() => hold(toast, 'focus')}
                         onBlur={() => release(toast, 'focus')}
                         className={`laravel-toast-item ${ts.bg} ${toast.show_border !== false ? 'border ' + ts.border : ''}`}
                         role="alert"
                         aria-live={toast.type === 'error' ? 'assertive' : 'polite'}
                         aria-atomic="true">
                        {toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position === 'top' && (
                            <div className={`laravel-toast-progress ${ts.barBg}`}><div className={`${ts.bar}`} style={{ width: `${progress[toast.id] ?? 100}%`, transition: 'none', ...(toast.progress_direction === 'rtl' ? { marginLeft: 'auto' } : {}) }} /></div>
                        )}
                        <div className="laravel-toast-body">
                            {toast.show_icon !== false && (
                                <div className="laravel-toast-icon">
                                    {toast.custom_icon ? <span dangerouslySetInnerHTML={{ __html: toast.custom_icon }} /> : <ToastIcon type={toast.type} className={ts.icon} />}
                                </div>
                            )}
                            <div className="laravel-toast-content" style={{ cursor: 'default' }}>
                                {toast.title && <p className="laravel-toast-title">{toast.title}</p>}
                                <p className="laravel-toast-message">{toast.message}</p>
                            </div>
                            {toast.show_close !== false && (
                                <button type="button" onClick={() => dismiss(toast.id)} className="laravel-toast-close" aria-label={dismissLabel}>
                                    <svg  fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" /></svg>
                                </button>
                            )}
                        </div>
                        {toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position !== 'top' && (
                            <div className={`laravel-toast-progress ${ts.barBg}`}><div className={`${ts.bar}`} style={{ width: `${progress[toast.id] ?? 100}%`, transition: 'none', ...(toast.progress_direction === 'rtl' ? { marginLeft: 'auto' } : {}) }} /></div>
                        )}
                    </div>
                )
            })}
        </div>
    ))
}
