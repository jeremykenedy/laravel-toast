import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import react from '@vitejs/plugin-react'
import { svelte } from '@sveltejs/vite-plugin-svelte'

export default defineConfig({
    resolve: { conditions: ['browser'] },
    plugins: [vue(), react(), svelte({ hot: false })],
    test: {
        environment: 'jsdom',
        include: ['tests/Frontend/**/*.test.{js,jsx}'],
        setupFiles: ['tests/Frontend/setup.js'],
    },
})
