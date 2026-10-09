export function appendToast(list, toast, stack) {
    const next = (stack ?? toast.stack ?? true) ? [...list, toast] : [toast]
    const max = Number(toast.max_visible ?? 5)
    return max > 0 ? next.slice(-max) : next
}

export function toastFramework(toast, override) {
    const value = override || toast.css_framework
    return ['bootstrap4', 'bootstrap5'].includes(value) ? value : 'tailwind'
}

export function bootstrapStyle(toast, override) {
    const framework = toastFramework(toast, override)
    if (framework === 'tailwind') return null
    const type = { success: 'success', error: 'danger', warning: 'warning', info: 'info' }[toast.type] || 'info'
    return {
        bg: framework === 'bootstrap5' ? `toast show text-bg-${type}` : `alert alert-${type}`,
        border: '', icon: '', bar: 'laravel-toast-bar', barBg: 'laravel-toast-track',
    }
}

export function listenForToasts(echo, channel, receive) {
    if (!echo || !channel) return () => {}
    const subscription = echo.private(channel)
    const listener = event => { if (event?.toast?.id) receive(event.toast) }
    subscription.listen('.toast', listener)
    return () => subscription.stopListening('.toast', listener)
}

// Every toast carries the generated color stylesheet, so SPA renderers that
// never see the Blade views still pick up the colors saved in settings.
export function applyToastColors(css, doc = typeof document !== 'undefined' ? document : null) {
    if (!doc) return
    let tag = doc.getElementById('toast-colors')
    if (!css) { if (tag) tag.remove(); return }
    if (!tag) { tag = doc.createElement('style'); tag.id = 'toast-colors'; doc.head.appendChild(tag) }
    if (tag.textContent !== css) tag.textContent = css
}
