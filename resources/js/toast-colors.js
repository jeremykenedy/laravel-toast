export const TOAST_TYPES = ['success', 'error', 'warning', 'info']
export const TOAST_MODES = ['light', 'dark']
export const TOAST_PARTS = ['background', 'text', 'border', 'icon', 'progress', 'track']

const PARTS = {
    background: { selector: '', properties: ['background-color'] },
    text: { selector: '', properties: ['color'] },
    border: { selector: '', properties: ['border-color'] },
    icon: { selector: ' [data-toast-part="icon"], %s [data-toast-part="icon"] svg', properties: ['color'] },
    progress: { selector: ' [data-toast-part="bar"]', properties: ['background-color'] },
    track: { selector: ' [data-toast-part="track"]', properties: ['background-color'] },
}

const CLOSE_ICON = '[data-laravel-toast]{--toast-close-icon:url("data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 16 16\'%3e%3cpath d=\'M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z\'/%3e%3c/svg%3e")}'

const CLOSE_RULES = {
    '.close': 'color:inherit!important',
    '.btn-close': 'color:inherit!important;filter:none!important;background-image:none!important;background-color:currentColor!important;-webkit-mask:var(--toast-close-icon) center/1em auto no-repeat;mask:var(--toast-close-icon) center/1em auto no-repeat',
}

const DARK_SCOPE = ':where(.dark, [data-bs-theme="dark"]) '

export function toastHex(value) {
    if (typeof value !== 'string' || !/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(value)) return null
    const hex = value.toLowerCase()
    return hex.length === 4 ? '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3] : hex
}

// Mirrors Support\ToastColors::css() so previews match what the server emits.
export function toastColorsCss(colors, scopes = {}) {
    const scope = { light: '', dark: DARK_SCOPE, ...scopes }
    const rules = []
    const anyText = TOAST_TYPES.some(type => TOAST_MODES.some(mode => toastHex(colors?.[type]?.[mode]?.text)))
    if (anyText) rules.push(CLOSE_ICON)

    for (const type of TOAST_TYPES) {
        for (const mode of TOAST_MODES) {
            const base = scope[mode] + '[data-laravel-toast][data-toast-type="' + type + '"]'

            for (const part of TOAST_PARTS) {
                const value = toastHex(colors?.[type]?.[mode]?.[part])
                if (!value) continue

                const definition = PARTS[part]
                const selector = definition.selector.includes('%s')
                    ? base + definition.selector.replace('%s', base)
                    : base + definition.selector
                rules.push(selector + '{' + definition.properties.map(property => property + ':' + value + '!important').join(';') + '}')

                if (part === 'text') {
                    for (const [closeSelector, declarations] of Object.entries(CLOSE_RULES)) rules.push(base + ' ' + closeSelector + '{' + declarations + '}')
                }
            }
        }
    }

    return rules.join('\n')
}

export const PREVIEW_SCOPES = {
    light: '[data-toast-preview="light"] ',
    dark: '[data-toast-preview="dark"] ',
}
