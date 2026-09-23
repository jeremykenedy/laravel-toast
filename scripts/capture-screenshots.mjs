import { mkdir } from 'node:fs/promises'
import { fileURLToPath } from 'node:url'
import { chromium, expect } from '@playwright/test'
import { createServer } from 'vite'

const root = fileURLToPath(new URL('../', import.meta.url))
const output = new URL('../art/screenshots/', import.meta.url)
const server = await createServer({ root, server: { host: '127.0.0.1', port: 4174, strictPort: true } })
let browser

const examples = [
    { type: 'success', title: 'Changes saved', message: 'Your changes have been saved.' },
    { type: 'error', title: 'Something went wrong', message: 'Please try again in a moment.' },
    { type: 'warning', title: 'Session expiring', message: 'Your session expires in 5 minutes.' },
    { type: 'info', title: 'Export ready', message: 'Your export is ready to download.' },
].map(toast => ({
    ...toast,
    id: `preview_${toast.type}`,
    position: 'top-center',
    stack: true,
    duration: 0,
    auto_dismiss: false,
    show_border: true,
    show_icon: true,
    show_close: true,
    enter_animation: 'none',
    exit_animation: 'none',
}))

try {
    await mkdir(output, { recursive: true })
    await server.listen()
    browser = await chromium.launch()

    for (const css of ['tailwind', 'bootstrap5', 'bootstrap4']) {
        for (const theme of ['light', 'dark']) {
            const page = await browser.newPage({
                viewport: { width: 432, height: 480 },
                deviceScaleFactor: 2,
                colorScheme: theme,
                reducedMotion: 'reduce',
            })
            const errors = []
            page.on('pageerror', error => errors.push(error.message))
            await page.goto(`http://127.0.0.1:4174/tests/Browser/index.html?frontend=react&css=${css}`)
            await page.waitForFunction(() => typeof window.setToasts === 'function')
            await page.evaluate(({ theme, examples }) => {
                document.documentElement.dataset.bsTheme = theme
                document.body.style.backgroundColor = theme === 'dark' ? '#0f172a' : '#f8fafc'
                document.querySelector('#host').hidden = true
                window.setToasts(examples)
            }, { theme, examples })
            const toasts = page.locator('[data-laravel-toast="component"]')
            await expect(toasts).toHaveCount(4)
            for (const example of examples) {
                await expect(page.getByText(example.message, { exact: true })).toBeVisible()
            }
            await page.evaluate(() => document.fonts.ready)
            const bounds = await toasts.last().boundingBox()
            await page.setViewportSize({ width: 432, height: Math.ceil(bounds.y + bounds.height + 24) })
            expect(errors).toEqual([])
            const filename = `${css}-${theme}.png`
            await page.screenshot({ path: fileURLToPath(new URL(filename, output)), animations: 'disabled' })
            console.log(`Captured art/screenshots/${filename}`)
            await page.close()
        }
    }
} finally {
    await browser?.close()
    await server.close()
}
