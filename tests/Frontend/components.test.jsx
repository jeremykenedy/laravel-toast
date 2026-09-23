import React, { StrictMode } from 'react'
import { describe, it, expect, vi } from 'vitest'
import { render as renderReact, act, fireEvent as reactEvent } from '@testing-library/react'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { tick } from 'svelte'
import VueToasts from '../../resources/js/vue/pages/ToastContainer.vue'
import ReactToasts from '../../resources/js/react/pages/ToastContainer.jsx'
import { mountSvelteToasts } from './mount-svelte.svelte.js'

const toast = (id, options = {}) => ({
    id, message: id, type: 'success', position: 'top-right', stack: true,
    duration: 1000, auto_dismiss: false, pause_on_hover: true, show_progress: true,
    progress_position: 'top', enter_animation: 'none', exit_animation: 'none',
    ...options,
})

const adapters = {
    vue: async props => {
        const wrapper = mount(VueToasts, { props, attachTo: document.body })
        await nextTick()
        return {
            element: document.body,
            update: async props => { await wrapper.setProps(props); await nextTick() },
            event: async (el, name) => { el.dispatchEvent(new Event(name, { bubbles: true })); await nextTick() },
            advance: async ms => { await vi.advanceTimersByTimeAsync(ms); await nextTick() },
            close: () => wrapper.unmount(),
        }
    },
    react: async props => {
        let current = props
        const wrapper = renderReact(<StrictMode><ReactToasts {...props}/></StrictMode>)
        return {
            element: wrapper.container,
            update: async props => { current = { ...current, ...props }; wrapper.rerender(<StrictMode><ReactToasts {...current}/></StrictMode>) },
            event: async (el, name) => { act(() => {
                if (name === 'mouseenter') reactEvent.mouseOver(el)
                else if (name === 'mouseleave') reactEvent.mouseOut(el)
                else reactEvent(el, new Event(name, { bubbles: true }))
            }) },
            advance: async ms => { await act(async () => { await vi.advanceTimersByTimeAsync(ms) }) },
            close: () => wrapper.unmount(),
        }
    },
    svelte: async props => {
        const target = document.createElement('div'); document.body.append(target)
        const component = mountSvelteToasts(target, props)
        await tick()
        return {
            element: target,
            update: async props => { component.update(props); await tick() },
            event: async (el, name) => {
                el.dispatchEvent(new Event(name, { bubbles: true }))
                await tick()
                // Svelte releases its delegated event reference in a zero-delay task.
                if (vi.isFakeTimers()) await vi.advanceTimersByTimeAsync(0)
            },
            advance: async ms => { await vi.advanceTimersByTimeAsync(ms); await tick() },
            close: () => { component.close(); target.remove() },
        }
    },
}

for (const [name, create] of Object.entries(adapters)) {
    describe(name, () => {
        it('receives later props, deduplicates IDs, and does not replay dismissed toasts', async () => {
            const ui = await create({ initialToasts: [] })
            try {
                await ui.update({ initialToasts: [toast('new toast')] })
                expect(ui.element.textContent).toContain('new toast')
                await ui.update({ initialToasts: [toast('new toast'), toast('second')] })
                expect(ui.element.querySelectorAll('[data-toast-id]')).toHaveLength(2)
                await ui.event(ui.element.querySelector('button'), 'click')
                await ui.update({ initialToasts: [toast('new toast'), toast('second')] })
                expect(ui.element.querySelectorAll('[data-toast-id]')).toHaveLength(1)
                expect(ui.element.textContent).not.toContain('new toast')
            } finally { ui.close() }
        })

        it('replaces across positions and never reveals superseded messages', async () => {
            const ui = await create({ stack: false, initialToasts: [toast('older'), toast('newest', { position: 'bottom-left' })] })
            try {
                expect(ui.element.querySelectorAll('[data-toast-id]')).toHaveLength(1)
                expect(ui.element.textContent).not.toContain('older')
                await ui.event(ui.element.querySelector('button'), 'click')
                expect(ui.element.querySelectorAll('[data-toast-id]')).toHaveLength(0)
            } finally { ui.close() }
        })

        it('honors payload position, stack, max_visible, and framework', async () => {
            const ui = await create({ initialToasts: [toast('older')] })
            try {
                await ui.update({ initialToasts: [toast('server options', { position: 'bottom-left', stack: false, css_framework: 'bootstrap5' })] })
                const el = ui.element.querySelector('[data-toast-id]')
                expect(el.classList.contains('text-bg-success')).toBe(true)
                expect(el.parentElement.style.bottom).toBe('0.5rem')
                expect(el.parentElement.style.left).toBe('0.5rem')
                expect(ui.element.textContent).not.toContain('older')
                await ui.update({ initialToasts: [toast('limited', { max_visible: 1 })] })
                expect(ui.element.querySelectorAll('[data-toast-id]')).toHaveLength(1)
            } finally { ui.close() }
        })

        for (const css of ['tailwind', 'bootstrap4', 'bootstrap5']) {
            it(`renders ${css} with its own color classes`, async () => {
                const ui = await create({ cssFramework: css, initialToasts: [toast('styled', { type: 'error' })] })
                try {
                    const el = ui.element.querySelector('[data-toast-id]')
                    expect(el.classList.contains({ tailwind: 'bg-red-50', bootstrap4: 'alert-danger', bootstrap5: 'text-bg-danger' }[css])).toBe(true)
                    if (css !== 'tailwind') expect(ui.element.innerHTML).not.toMatch(/(?:bg|text|border)-(?:red|green|amber|blue)-\d/)
                } finally { ui.close() }
            })
        }

        it('auto dismisses new props and cancels timers on unmount', async () => {
            vi.useFakeTimers()
            const ui = await create({ initialToasts: [] })
            await ui.update({ initialToasts: [toast('timed', { auto_dismiss: true })] })
            await ui.advance(1100)
            expect(ui.element.querySelectorAll('[data-toast-id]')).toHaveLength(0)
            await ui.update({ initialToasts: [toast('pending', { auto_dismiss: true, exit_animation: 'fade', exit_duration: 2 })] })
            await ui.event(ui.element.querySelector('button'), 'click')
            ui.close()
            expect(vi.getTimerCount()).toBe(0)
        })

        it('holds a countdown until both hover and keyboard focus release', async () => {
            vi.useFakeTimers()
            const ui = await create({ initialToasts: [toast('paused', { auto_dismiss: true })] })
            try {
                const el = ui.element.querySelector('[data-toast-id]')
                await ui.advance(250)
                await ui.event(el, 'mouseenter')
                await ui.event(el, 'focusin')
                await ui.advance(2000)
                await ui.event(el, 'focusout')
                await ui.advance(2000)
                expect(ui.element.querySelectorAll('[data-toast-id]')).toHaveLength(1)
                await ui.event(el, 'mouseleave')
                await ui.advance(600)
                expect(ui.element.querySelectorAll('[data-toast-id]')).toHaveLength(1)
                await ui.advance(200)
                expect(ui.element.querySelectorAll('[data-toast-id]')).toHaveLength(0)
            } finally { ui.close() }
        })

        it('cancels superseded timers when replacing an animated toast', async () => {
            vi.useFakeTimers()
            const ui = await create({ stack: false, initialToasts: [toast('old', { auto_dismiss: true, exit_animation: 'fade', exit_duration: 2 })] })
            try {
                await ui.event(ui.element.querySelector('button'), 'click')
                await ui.update({ initialToasts: [toast('replacement')] })
                expect(vi.getTimerCount()).toBe(0)
                await ui.advance(2500)
                expect(ui.element.textContent).toContain('replacement')
            } finally { ui.close() }
        })

        it('subscribes to private broadcasts and removes only its own listener', async () => {
            let listener
            const subscription = { listen: vi.fn((event, callback) => { listener = callback }), stopListening: vi.fn() }
            const echo = { private: vi.fn(() => subscription) }
            const ui = await create({ echo, channel: 'toast.42', initialToasts: [] })
            try {
                if (name === 'react') await act(async () => listener({ toast: toast('broadcast') }))
                else { listener({ toast: toast('broadcast') }); await ui.update({}) }
                expect(ui.element.textContent).toContain('broadcast')
                expect(echo.private).toHaveBeenCalledWith('toast.42')
            } finally { ui.close() }
            expect(subscription.stopListening).toHaveBeenCalledWith('.toast', expect.any(Function))
        })
    })
}
