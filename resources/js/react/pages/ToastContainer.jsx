import { __ } from "@/i18n/translator"
import React, { useState, useEffect, useCallback, useRef } from 'react'

const styles = {
    success: { bg: 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200', border: 'border-green-200 dark:border-green-800', icon: 'text-green-500 dark:text-green-400', bar: 'bg-green-500 dark:bg-green-400', barBg: 'bg-green-200 dark:bg-green-900' },
    error: { bg: 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200', border: 'border-red-200 dark:border-red-800', icon: 'text-red-500 dark:text-red-400', bar: 'bg-red-500 dark:bg-red-400', barBg: 'bg-red-200 dark:bg-red-900' },
    warning: { bg: 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-200', border: 'border-amber-200 dark:border-amber-800', icon: 'text-amber-500 dark:text-amber-400', bar: 'bg-amber-500 dark:bg-amber-400', barBg: 'bg-amber-200 dark:bg-amber-900' },
    info: { bg: 'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200', border: 'border-blue-200 dark:border-blue-800', icon: 'text-blue-500 dark:text-blue-400', bar: 'bg-blue-500 dark:bg-blue-400', barBg: 'bg-blue-200 dark:bg-blue-900' },
}

const positionMap = {
    'top-left': 'top:0.5rem;left:0.5rem;', 'top-center': 'top:0.5rem;left:50%;transform:translateX(-50%);',
    'top-right': 'top:0.5rem;right:0.5rem;', 'bottom-right': 'bottom:0.5rem;right:0.5rem;',
    'bottom-left': 'bottom:0.5rem;left:0.5rem;', 'bottom-center': 'bottom:0.5rem;left:50%;transform:translateX(-50%);',
}

function ToastIcon({ type, className }) {
    const paths = {
        success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        error: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    }
    return <svg className={`h-5 w-5 ${className}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d={paths[type] || paths.info} /></svg>
}

export default function ToastContainer({ initialToasts = [], position = 'top-right' }) {
    const [toasts, setToasts] = useState([])
    const progressRef = useRef({})
    const timersRef = useRef({})
    const pausedRef = useRef({})

    const dismiss = useCallback((id) => {
        const toast = toasts.find(t => t.id === id)
        if (timersRef.current[id]) { cancelAnimationFrame(timersRef.current[id]); delete timersRef.current[id] }
        const anim = toast?.exit_animation || 'none'
        const dur = toast?.exit_duration || 0.5
        if (anim !== 'none') {
            const el = document.querySelector(`[data-toast-id="${id}"]`)
            if (el) { el.style.animation = `toast-${anim} ${dur}s ease forwards` }
            setTimeout(() => setToasts(prev => prev.filter(t => t.id !== id)), dur * 1000)
        } else { setToasts(prev => prev.filter(t => t.id !== id)) }
    }, [toasts])

    useEffect(() => {
        const initial = initialToasts.length ? initialToasts : (window.__toasts || [])
        setToasts(initial)
        initial.forEach(toast => {
            if (toast.auto_dismiss && toast.duration > 0) {
                progressRef.current[toast.id] = 100
                const start = Date.now()
                function tick() {
                    if (pausedRef.current[toast.id]) return
                    const elapsed = Date.now() - start
                    const pct = Math.max(0, 100 - (elapsed / toast.duration * 100))
                    progressRef.current[toast.id] = pct
                    if (pct <= 0) { setToasts(prev => prev.filter(t => t.id !== toast.id)) }
                    else { timersRef.current[toast.id] = requestAnimationFrame(tick) }
                }
                timersRef.current[toast.id] = requestAnimationFrame(tick)
            }
        })
    }, [])

    const s = (toast) => styles[toast.type] || styles.info
    const posStyle = positionMap[position] || positionMap['top-right']

    return toasts.length ? (
        <div style={{ position: 'fixed', zIndex: 9999, width: '24rem', maxWidth: 'calc(100vw - 2rem)', display: 'flex', flexDirection: 'column', gap: '0.75rem', ...Object.fromEntries(posStyle.split(';').filter(Boolean).map(p => { const [k,v] = p.split(':'); return [k.trim(), v.trim()] })) }} role="status" aria-live="polite">
            {toasts.map(toast => {
                const ts = s(toast)
                const enterStyle = (toast.enter_animation && toast.enter_animation !== 'none') ? { animation: `toast-enter-${toast.enter_animation} ${toast.enter_duration || 0.5}s ease forwards` } : {}
                return (
                    <div key={toast.id} data-toast-id={toast.id} dir={toast.dir || 'ltr'}
                         style={{ cursor: 'default', ...(toast.opacity < 1 ? { opacity: toast.opacity } : {}), ...enterStyle }}
                         onMouseEnter={() => toast.pause_on_hover && (pausedRef.current[toast.id] = true)}
                         onMouseLeave={() => { if (toast.pause_on_hover) { delete pausedRef.current[toast.id] } }}
                         className={`rounded-lg shadow-lg overflow-hidden ${ts.bg} ${toast.show_border !== false ? 'border ' + ts.border : ''}`}
                         role="alert">
                        {toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position === 'top' && (
                            <div className={`h-1 w-full ${ts.barBg}`}><div className={`h-full ${ts.bar}`} style={{ width: `${progressRef.current[toast.id] ?? 100}%`, transition: 'none', ...(toast.progress_direction === 'rtl' ? { marginLeft: 'auto' } : {}) }} /></div>
                        )}
                        <div className="p-4 flex items-start gap-3">
                            {toast.show_icon !== false && (
                                <div className="flex-shrink-0 mt-0.5">
                                    {toast.custom_icon ? <span dangerouslySetInnerHTML={{ __html: toast.custom_icon }} /> : <ToastIcon type={toast.type} className={ts.icon} />}
                                </div>
                            )}
                            <div className="flex-1 min-w-0" style={{ cursor: 'default' }}>
                                {toast.title && <p className="text-sm font-semibold">{toast.title}</p>}
                                <p className="text-sm">{toast.message}</p>
                            </div>
                            {toast.show_close !== false && (
                                <button onClick={() => dismiss(toast.id)} className="flex-shrink-0 rounded-md p-1 opacity-60 hover:opacity-100 transition-opacity cursor-pointer" aria-label="Dismiss">
                                    <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" /></svg>
                                </button>
                            )}
                        </div>
                        {toast.auto_dismiss && toast.show_progress !== false && toast.duration > 0 && toast.progress_position !== 'top' && (
                            <div className={`h-1 w-full ${ts.barBg}`}><div className={`h-full ${ts.bar}`} style={{ width: `${progressRef.current[toast.id] ?? 100}%`, transition: 'none', ...(toast.progress_direction === 'rtl' ? { marginLeft: 'auto' } : {}) }} /></div>
                        )}
                    </div>
                )
            })}
        </div>
    ) : null
}
