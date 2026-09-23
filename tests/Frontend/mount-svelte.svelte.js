import { flushSync, mount, unmount } from 'svelte'
import ToastContainer from '../../resources/js/svelte/pages/ToastContainer.svelte'

export function mountSvelteToasts(target, initialProps) {
    const props = $state({ ...initialProps })
    const component = mount(ToastContainer, { target, props })
    flushSync()

    return {
        update(next) { Object.assign(props, next); flushSync() },
        close() { unmount(component); flushSync() },
    }
}
