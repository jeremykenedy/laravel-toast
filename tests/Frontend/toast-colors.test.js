import { describe, it, expect, afterEach } from 'vitest'
import { readFileSync } from 'node:fs'
import { toastColorsCss, toastHex, PREVIEW_SCOPES } from '../../resources/js/toast-colors.js'
import { applyToastColors } from '../../resources/js/toast-options.js'

const input = JSON.parse(readFileSync('tests/fixtures/toast-colors-input.json', 'utf8'))
const expected = readFileSync('tests/fixtures/toast-colors-expected.css', 'utf8')

describe('toastColorsCss', () => {
    it('matches the PHP generator for the shared fixture', () => {
        expect(toastColorsCss(input) + '\n').toBe(expected)
    })

    it('accepts only hex colors and expands short hex', () => {
        expect(toastHex('#ABC')).toBe('#aabbcc')
        expect(toastHex('red')).toBeNull()
        expect(toastHex('#fff;}body{display:none')).toBeNull()
        expect(toastHex('')).toBeNull()
    })

    it('uses custom scopes for the preview', () => {
        const css = toastColorsCss({ info: { dark: { border: '#ffffff' } } }, PREVIEW_SCOPES)
        expect(css).toBe('[data-toast-preview="dark"] [data-laravel-toast][data-toast-type="info"]{border-color:#ffffff!important}')
    })

    it('makes Bootstrap close buttons follow a custom text color', () => {
        const css = toastColorsCss({ success: { light: { text: '#115e59' } } })
        expect(css).toContain('[data-laravel-toast][data-toast-type="success"] .close{color:inherit!important}')
        expect(css).toContain('[data-laravel-toast][data-toast-type="success"] .btn-close{color:inherit!important;filter:none!important')
        expect(css.match(/--toast-close-icon:url\(/g)).toHaveLength(1)
        expect(toastColorsCss({ success: { light: { background: '#ecfdf5' } } })).not.toContain('.btn-close')
    })

    it('returns nothing when no color is set', () => {
        expect(toastColorsCss({})).toBe('')
        expect(toastColorsCss(undefined)).toBe('')
    })
})

describe('applyToastColors', () => {
    afterEach(() => document.getElementById('toast-colors')?.remove())

    it('injects, updates and removes the stylesheet', () => {
        applyToastColors('a{color:#111111}')
        expect(document.querySelectorAll('#toast-colors')).toHaveLength(1)
        expect(document.getElementById('toast-colors').textContent).toBe('a{color:#111111}')

        applyToastColors('b{color:#222222}')
        expect(document.querySelectorAll('#toast-colors')).toHaveLength(1)
        expect(document.getElementById('toast-colors').textContent).toBe('b{color:#222222}')

        applyToastColors('')
        expect(document.getElementById('toast-colors')).toBeNull()
    })
})
