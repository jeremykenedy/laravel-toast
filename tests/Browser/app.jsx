import React, { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { createApp, reactive, h } from 'vue'
import ReactToasts from '../../resources/js/react/pages/ToastContainer.jsx'
import VueToasts from '../../resources/js/vue/pages/ToastContainer.vue'
import { mountSvelteToasts } from '../Frontend/mount-svelte.svelte.js'

const params = new URLSearchParams(location.search)
const cssFramework = params.get('css') || 'tailwind'
const frontend = params.get('frontend') || 'react'
if (cssFramework === 'tailwind') await import('./tailwind.generated.css')
if (cssFramework === 'bootstrap4') await import('bootstrap4/dist/css/bootstrap.css')
if (cssFramework === 'bootstrap5') await import('bootstrap5/dist/css/bootstrap.css')
document.querySelector('#host').className = cssFramework === 'bootstrap4' ? 'alert alert-success' : 'text-bg-success'
const target = document.querySelector('#app')
if (frontend === 'react') {
    const root = createRoot(target)
    window.setToasts = initialToasts => root.render(<StrictMode><ReactToasts cssFramework={cssFramework} initialToasts={initialToasts}/></StrictMode>)
} else if (frontend === 'vue') {
    const props = reactive({ initialToasts: [], cssFramework })
    createApp({ render: () => h(VueToasts, props) }).mount(target)
    window.setToasts = initialToasts => { props.initialToasts = initialToasts }
} else {
    const component = mountSvelteToasts(target, { initialToasts: [], cssFramework })
    window.setToasts = initialToasts => component.update({ initialToasts })
}
window.setToasts([])
