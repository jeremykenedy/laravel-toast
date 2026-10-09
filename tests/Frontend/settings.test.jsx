import React from 'react'
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { render as renderReact, act, fireEvent as reactEvent, cleanup } from '@testing-library/react'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { tick } from 'svelte'
import VueSettings from '../../resources/js/vue/pages/ToastSettings.vue'
import ReactSettings from '../../resources/js/react/pages/ToastSettings.jsx'
import { mountSvelteSettings } from './mount-svelte-settings.svelte.js'

const matrix = colors => colors
const payload = (overrides = {}) => ({
    options: { position: 'top-right', duration: 5000, opacity: 1, show_icons: true, show_close: true, show_progress: true, show_border: true },
    colors: matrix({ success: { light: { background: '#ecfdf5' } } }),
    defaults: { options: {}, colors: {} },
    fields: {
        position: { type: 'select', options: ['top-right', 'top-left'] },
        duration: { type: 'number', min: 0, max: 3600000, step: 100 },
        opacity: { type: 'number', min: 0, max: 1, step: 0.05 },
        show_icons: { type: 'checkbox' },
        show_close: { type: 'checkbox' },
        show_progress: { type: 'checkbox' },
        show_border: { type: 'checkbox' },
    },
    types: ['success', 'error', 'warning', 'info'],
    parts: ['background', 'text', 'border', 'icon', 'progress', 'track'],
    ready: true,
    ...overrides,
})

const json = (body, status = 200) => Promise.resolve({ ok: status < 400, status, json: () => Promise.resolve(body) })

const adapters = {
    vue: async props => {
        const wrapper = mount(VueSettings, { props, attachTo: document.body })
        await nextTick(); await nextTick()
        return {
            root: () => document.body,
            input: async (el, value) => { el.value = value; el.dispatchEvent(new Event('input', { bubbles: true })); await nextTick() },
            click: async el => { el.click(); await nextTick() },
            submit: async form => { form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true })); await nextTick() },
            settle: async () => { await new Promise(r => setTimeout(r, 0)); await nextTick() },
            close: () => wrapper.unmount(),
        }
    },
    react: async props => {
        const wrapper = renderReact(<ReactSettings {...props} />)
        await act(async () => {})
        return {
            root: () => wrapper.container,
            input: async (el, value) => { await act(async () => { reactEvent.change(el, { target: { value } }) }) },
            click: async el => { await act(async () => { reactEvent.click(el) }) },
            submit: async form => { await act(async () => { reactEvent.submit(form) }) },
            settle: async () => { await act(async () => { await new Promise(r => setTimeout(r, 0)) }) },
            close: () => { wrapper.unmount(); cleanup() },
        }
    },
    svelte: async props => {
        const target = document.createElement('div'); document.body.append(target)
        const component = mountSvelteSettings(target, props)
        await tick()
        return {
            root: () => target,
            input: async (el, value) => { el.value = value; el.dispatchEvent(new Event('input', { bubbles: true })); await tick() },
            click: async el => { el.click(); await tick() },
            submit: async form => { form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true })); await tick() },
            settle: async () => { await new Promise(r => setTimeout(r, 0)); await tick() },
            close: () => { component.close(); target.remove() },
        }
    },
}

const colorInput = (root, key) => root.querySelector(`input[data-color="${key}"]`)
const previewCss = root => root.querySelector('[data-toast-preview-style]').textContent

for (const [name, create] of Object.entries(adapters)) {
    describe(`${name} settings`, () => {
        let fetchMock

        beforeEach(() => {
            fetchMock = vi.fn(() => json(payload()))
            vi.stubGlobal('fetch', fetchMock)
            document.head.innerHTML = '<meta name="csrf-token" content="meta-token">'
        })
        afterEach(() => { vi.unstubAllGlobals(); document.head.innerHTML = '' })

        it('renders the payload: colors, preview containers and behavior fields', async () => {
            const ui = await create({ initial: payload() })
            try {
                const root = ui.root()
                expect(root.querySelectorAll('input[type="color"]')).toHaveLength(4 * 2 * 6)
                expect(colorInput(root, 'success.light.background').value).toBe('#ecfdf5')
                expect(root.querySelectorAll('[data-toast-preview="light"] [data-laravel-toast="preview"]')).toHaveLength(4)
                expect(root.querySelectorAll('[data-toast-preview="dark"] [data-toast-type="warning"]')).toHaveLength(1)
                expect(root.querySelector('[data-toast-preview="dark"]').classList.contains('dark')).toBe(true)
                expect(root.querySelector('select[name="position"]')).not.toBeNull()
                expect(root.querySelector('input[name="show_close"]').checked).toBe(true)
                expect(previewCss(root)).toContain('[data-toast-preview="light"] [data-laravel-toast][data-toast-type="success"]{background-color:#ecfdf5!important}')
                expect(fetchMock).not.toHaveBeenCalled()
            } finally { ui.close() }
        })

        it('loads the payload from the endpoint when none is passed', async () => {
            const ui = await create({ endpoint: '/admin/toast' })
            try {
                await ui.settle()
                expect(fetchMock).toHaveBeenCalledWith('/admin/toast/data', expect.objectContaining({ method: 'GET' }))
                expect(ui.root().querySelectorAll('input[type="color"]')).toHaveLength(48)
            } finally { ui.close() }
        })

        it('updates the preview and the saved body when a color changes', async () => {
            const ui = await create({ initial: payload(), csrf: 'prop-token' })
            try {
                const root = ui.root()
                await ui.input(colorInput(root, 'error.dark.icon'), '#ff0000')
                expect(previewCss(root)).toContain('[data-toast-preview="dark"] [data-laravel-toast][data-toast-type="error"] [data-toast-part="icon"]')
                expect(previewCss(root)).toContain('color:#ff0000!important')

                await ui.submit(root.querySelector('form'))
                await ui.settle()

                const [url, request] = fetchMock.mock.calls.at(-1)
                const body = JSON.parse(request.body)
                expect(url).toBe('/toast/settings')
                expect(request.method).toBe('PUT')
                expect(request.headers).toMatchObject({ Accept: 'application/json', 'X-CSRF-TOKEN': 'prop-token', 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' })
                expect(body.colors.error.dark.icon).toBe('#ff0000')
                expect(body.colors.success.light.background).toBe('#ecfdf5')
                expect(body.colors.success.light.text).toBeNull()
                expect(body.options).toMatchObject({ position: 'top-right', duration: 5000, show_close: true })
                expect(root.querySelector('[role="status"]').textContent).toBe('Notification settings saved.')
            } finally { ui.close() }
        })

        it('falls back to the csrf meta tag', async () => {
            const ui = await create({ initial: payload() })
            try {
                await ui.submit(ui.root().querySelector('form'))
                await ui.settle()
                expect(fetchMock.mock.calls.at(-1)[1].headers['X-CSRF-TOKEN']).toBe('meta-token')
            } finally { ui.close() }
        })

        it('clears a color to null with Default', async () => {
            const ui = await create({ initial: payload() })
            try {
                const root = ui.root()
                const clear = [...root.querySelectorAll('[data-toast-color-row]')][0].querySelector('button')
                await ui.click(clear)
                expect(previewCss(root)).not.toContain('#ecfdf5')

                await ui.submit(root.querySelector('form'))
                await ui.settle()
                expect(JSON.parse(fetchMock.mock.calls.at(-1)[1].body).colors.success.light.background).toBeNull()
            } finally { ui.close() }
        })

        it('hides preview parts and applies opacity from the behavior options', async () => {
            const ui = await create({ initial: payload() })
            try {
                const root = ui.root()
                await ui.click(root.querySelector('input[name="show_icons"]'))
                await ui.input(root.querySelector('input[name="opacity"]'), '0.5')
                expect(previewCss(root)).toContain('[data-toast-part="icon"]{display:none}')
                expect(previewCss(root)).toContain('opacity:0.5')
            } finally { ui.close() }
        })

        it('sends DELETE on reset and adopts the response', async () => {
            const ui = await create({ initial: payload() })
            try {
                fetchMock.mockImplementationOnce(() => json(payload({ colors: {} })))
                const reset = [...ui.root().querySelectorAll('button')].find(b => b.textContent === 'Reset all to defaults')
                await ui.click(reset)
                await ui.settle()

                const [url, request] = fetchMock.mock.calls.at(-1)
                expect(url).toBe('/toast/settings')
                expect(request.method).toBe('DELETE')
                expect(request.body).toBeUndefined()
                expect(colorInput(ui.root(), 'success.light.background').value).toBe('#888888')
                expect(ui.root().querySelector('[role="status"]').textContent).toBe('Notification settings reset to defaults.')
            } finally { ui.close() }
        })

        it('disables save and reset when the table is not ready', async () => {
            const ui = await create({ initial: payload({ ready: false }) })
            try {
                const buttons = [...ui.root().querySelectorAll('.ts-primary, .ts-secondary')]
                expect(buttons).toHaveLength(2)
                expect(buttons.every(b => b.disabled)).toBe(true)
                expect(ui.root().querySelector('[role="alert"]').textContent).toContain('settings table does not exist')
            } finally { ui.close() }
        })

        it('shows the server message on a 409', async () => {
            const ui = await create({ initial: payload() })
            try {
                fetchMock.mockImplementationOnce(() => json({ message: 'Run the toast settings migration before saving.' }, 409))
                await ui.submit(ui.root().querySelector('form'))
                await ui.settle()
                expect(ui.root().querySelector('[role="status"]').textContent).toBe('Run the toast settings migration before saving.')
                expect(ui.root().querySelector('[role="status"]').getAttribute('data-status')).toBe('error')
            } finally { ui.close() }
        })

        it('uses custom labels', async () => {
            const ui = await create({ initial: payload(), labels: { title: 'Meldungen', types: { success: 'Erfolg' } } })
            try {
                expect(ui.root().querySelector('h2').textContent).toBe('Meldungen')
                expect(ui.root().textContent).toContain('Erfolg')
                expect(ui.root().textContent).toContain('Error')
            } finally { ui.close() }
        })
    })
}
