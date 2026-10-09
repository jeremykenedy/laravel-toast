import { TOAST_MODES, TOAST_PARTS, TOAST_TYPES, PREVIEW_SCOPES, toastColorsCss, toastHex } from './toast-colors.js'

// English fallbacks. Keys match the 'settings' block in resources/lang/en/toast.php.
export const DEFAULT_LABELS = {
    title: 'Notification settings',
    description: 'Choose how notifications look and behave. Previews update as you edit; nothing is saved until you press Save.',
    not_ready: 'The settings table does not exist yet. Publish and run the migration, then reload this page.',
    colors: 'Colors',
    colors_help: 'Leave a color on Default to keep the framework color. Light and dark colors are independent.',
    options: 'Behavior',
    preview: 'Preview',
    light: 'Light',
    dark: 'Dark',
    default: 'Default',
    save: 'Save settings',
    saving: 'Saving...',
    reset: 'Reset all to defaults',
    reset_confirm: 'Remove all saved notification settings?',
    sample_message: 'This is how this notification will look.',
    loading: 'Loading...',
    saved_message: 'Notification settings saved.',
    reset_message: 'Notification settings reset to defaults.',
    request_failed: 'The request failed. Try again.',
    types: { success: 'Success', error: 'Error', warning: 'Warning', info: 'Info' },
    parts: { background: 'Background', text: 'Text', border: 'Border', icon: 'Icon', progress: 'Progress bar', track: 'Progress track' },
    fields: {
        position: 'Position', dir: 'Text direction', duration: 'Duration (ms)', max_visible: 'Max visible', opacity: 'Opacity',
        enter_animation: 'Enter animation', enter_duration: 'Enter duration (s)', exit_animation: 'Exit animation',
        exit_duration: 'Exit duration (s)', progress_direction: 'Progress direction', progress_position: 'Progress position',
        auto_dismiss: 'Auto dismiss', pause_on_hover: 'Pause on hover', stack: 'Stack toasts', show_icons: 'Show icons',
        show_border: 'Show border', show_close: 'Show close button', show_progress: 'Show progress bar',
        convert_flash: 'Convert flash messages',
    },
}

export const SAMPLE_ICON_PATHS = {
    success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    error: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
}

export function mergeLabels(labels = {}) {
    const merged = { ...DEFAULT_LABELS, ...labels }
    for (const group of ['types', 'parts', 'fields']) merged[group] = { ...DEFAULT_LABELS[group], ...(labels?.[group] || {}) }
    return merged
}

export function csrfToken(explicit) {
    if (explicit) return explicit
    if (typeof document === 'undefined') return ''
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
}

// Full type/mode/part matrix; an empty string means Default.
export function colorMatrix(colors) {
    const matrix = {}
    for (const type of TOAST_TYPES) {
        matrix[type] = {}
        for (const mode of TOAST_MODES) {
            matrix[type][mode] = {}
            for (const part of TOAST_PARTS) matrix[type][mode][part] = toastHex(colors?.[type]?.[mode]?.[part]) || ''
        }
    }
    return matrix
}

export function cloneMatrix(matrix) {
    return colorMatrix(matrix)
}

export function setColor(matrix, type, mode, part, value) {
    const next = cloneMatrix(matrix)
    next[type][mode][part] = value || ''
    return next
}

export function colorsBody(matrix) {
    const body = {}
    for (const type of TOAST_TYPES) {
        body[type] = {}
        for (const mode of TOAST_MODES) {
            body[type][mode] = {}
            for (const part of TOAST_PARTS) body[type][mode][part] = matrix?.[type]?.[mode]?.[part] || null
        }
    }
    return body
}

export function optionsBody(options, fields) {
    const body = {}
    for (const [key, field] of Object.entries(fields || {})) {
        const value = options?.[key]
        if (field.type === 'checkbox') body[key] = Boolean(value)
        else if (field.type === 'number') body[key] = Number(value)
        else body[key] = value
    }
    return body
}

export function previewCss(matrix, options = {}) {
    const rules = [toastColorsCss(matrix, PREVIEW_SCOPES)]
    const hidden = {
        show_icons: '[data-toast-preview] [data-toast-part="icon"]{display:none}',
        show_close: '[data-toast-preview] [data-toast-part="close"]{display:none}',
        show_progress: '[data-toast-preview] [data-toast-part="track"]{display:none}',
        show_border: '[data-toast-preview] [data-laravel-toast]{border-width:0!important}',
    }
    for (const [key, rule] of Object.entries(hidden)) if (!options[key]) rules.push(rule)

    const opacity = Number(options.opacity ?? 1)
    if (opacity < 1) rules.push('[data-toast-preview] [data-laravel-toast]{opacity:' + opacity + '}')

    return rules.filter(Boolean).join('\n')
}

export async function settingsRequest(url, method, csrf, body) {
    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    if (csrf) headers['X-CSRF-TOKEN'] = csrf
    if (body !== undefined) headers['Content-Type'] = 'application/json'

    const response = await fetch(url, { method, headers, credentials: 'same-origin', body: body === undefined ? undefined : JSON.stringify(body) })
    let data = null
    try { data = await response.json() } catch { data = null }

    return { ok: response.ok, status: response.status, data }
}

export function loadPayload(endpoint) {
    return settingsRequest(`${endpoint}/data`, 'GET', '')
}

export function errorMessage(result, labels) {
    return result?.data?.message || labels.request_failed
}
