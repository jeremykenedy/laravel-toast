import { flushSync, mount, unmount } from 'svelte'
import ToastSettings from '../../resources/js/svelte/pages/ToastSettings.svelte'

export function mountSvelteSettings(target, props) {
    const component = mount(ToastSettings, { target, props })
    flushSync()

    return { close() { unmount(component); flushSync() } }
}
