import { afterEach, vi } from 'vitest'

window.matchMedia = vi.fn(() => ({ matches: false, addEventListener() {}, removeEventListener() {} }))
afterEach(() => { vi.useRealTimers(); delete window.__toasts })
