import { test, expect } from '@playwright/test'

const payload = { id: 'browser_toast', message: 'Saved', type: 'success', duration: 0, auto_dismiss: false, pause_on_hover: true, position: 'bottom-left', show_border: true, show_icon: true, exit_animation: 'fade', exit_duration: .2 }

for (const frontend of ['vue', 'react', 'svelte']) {
    for (const css of ['tailwind', 'bootstrap4', 'bootstrap5']) {
        test(`${frontend} with ${css} renders styled updates and animates dismissal`, async ({ page }) => {
            const errors = []
            page.on('pageerror', error => errors.push(error.message))
            await page.goto(`/tests/Browser/index.html?frontend=${frontend}&css=${css}`)
            await page.waitForFunction(() => typeof window.setToasts === 'function')
            await page.evaluate(toast => window.setToasts([toast]), payload)
            const toast = page.locator('[data-toast-id="browser_toast"]')
            await expect(toast).toBeVisible()
            await expect(toast).not.toHaveCSS('background-color', 'rgba(0, 0, 0, 0)')
            await expect(toast.locator('.laravel-toast-icon svg')).toHaveCSS('width', '20px')
            await expect(toast.locator('..')).toHaveCSS('bottom', '8px')
            await toast.getByRole('button', { name: 'Dismiss' }).click()
            await expect(toast).toHaveCount(0)
            expect(errors).toEqual([])
        })
    }
}

for (const css of ['bootstrap4', 'bootstrap5']) {
    test(`${css} isolates dark styles and honors explicit light mode`, async ({ page }) => {
        await page.goto(`/tests/Browser/index.html?frontend=react&css=${css}`)
        await page.waitForFunction(() => typeof window.setToasts === 'function')
        await page.evaluate(toast => { document.documentElement.dataset.bsTheme = 'light'; window.setToasts([toast]) }, payload)
        const toast = page.locator('[data-toast-id]')
        await expect(toast).toBeVisible()
        const light = await toast.evaluate(el => getComputedStyle(el).backgroundColor)
        const host = await page.locator('#host').evaluate(el => getComputedStyle(el).backgroundColor)
        await page.emulateMedia({ colorScheme: 'dark' })
        await expect(toast).toHaveCSS('background-color', light)
        await page.evaluate(() => { document.documentElement.dataset.bsTheme = 'dark' })
        await expect(toast).toHaveCSS('background-color', 'rgb(6, 78, 59)')
        await expect(page.locator('#host')).toHaveCSS('background-color', host)
    })
}
