import { readFileSync } from 'node:fs'
import { JSDOM } from 'jsdom'
import { describe, it, expect, vi } from 'vitest'

function setup(renderer, options = {}) {
    const livewire = renderer === 'livewire'
    const path = livewire ? 'resources/views/livewire/partials/timer-script.blade.php' : `resources/views/${renderer}/blade/toasts.blade.php`
    const script = readFileSync(path, 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1]
    const dom = new JSDOM(`<div wire:id="component"><div id="lw-toast-toast_1" data-laravel-toast="${livewire ? 'livewire' : 'blade'}" data-auto-dismiss="${options.autoDismiss ?? true}" data-duration="5000" data-pause-on-hover="true" data-exit-animation="fade" data-exit-duration="0.5"><div class="toast-progress-bar"></div><button data-toast-dismiss class="${renderer === 'bootstrap4' ? 'close' : 'btn-close'}">Close</button></div></div>`, { runScripts: 'outside-only' })
    const win = dom.window
    const frames = new Map(), timeouts = new Map()
    let frameId = 0, now = 0
    win.Date.now = () => now
    win.requestAnimationFrame = callback => { frames.set(++frameId, callback); return frameId }
    win.cancelAnimationFrame = id => frames.delete(id)
    win.setTimeout = (callback, ms) => { timeouts.set(callback, now + ms); return callback }
    const call = vi.fn()
    win.Livewire = { find: () => ({ call }), hook: vi.fn() }
    win.eval(script)
    if (!livewire) win.document.dispatchEvent(new win.Event('DOMContentLoaded'))
    const el = win.document.querySelector('[data-laravel-toast]')
    return {
        frames, call, el,
        event(name) { el.dispatchEvent(new win.Event(name)) },
        click() { el.querySelector('button').dispatchEvent(new win.Event('click', { bubbles: true })) },
        advance(ms) {
            now += ms
            const pending = [...frames.values()]; frames.clear(); pending.forEach(callback => callback())
            for (const [callback, at] of timeouts) { if (at <= now) { timeouts.delete(callback); callback() } }
        },
        close: () => dom.window.close(),
    }
}

for (const renderer of ['bootstrap4', 'bootstrap5', 'livewire']) {
    describe(renderer, () => {
        it('keeps a single countdown through same-frame hover and focus changes', () => {
            const ui = setup(renderer)
            try {
                expect(ui.frames.size).toBe(1)
                ui.event('mouseenter'); ui.event('focusin'); ui.event('focusout')
                expect(ui.frames.size).toBe(0)
                ui.event('mouseleave')
                expect(ui.frames.size).toBe(1)
                ui.advance(100)
                expect(ui.frames.size).toBe(1)
            } finally { ui.close() }
        })
        it('plays the exit animation on manual dismissal even without auto dismiss', () => {
            const ui = setup(renderer, { autoDismiss: false })
            try {
                ui.click()
                expect(ui.el.style.animation).toBe('toast-fade 0.5s ease forwards')
                expect(ui.el.isConnected).toBe(true)
                expect(ui.call).not.toHaveBeenCalled()
                ui.advance(500)
                if (renderer === 'livewire') expect(ui.call).toHaveBeenCalledWith('dismiss', 'toast_1')
                else expect(ui.el.isConnected).toBe(false)
            } finally { ui.close() }
        })
    })
}
