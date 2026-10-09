// Progressive enhancement for the Blade settings panel. The form posts without
// it; this adds the live preview and the per-color reset.
(function (root) {
    function read(form) {
        var colors = {}
        form.querySelectorAll('[data-toast-color-value]').forEach(function (input) {
            var path = input.getAttribute('data-toast-color-value').split('.')
            colors[path[0]] = colors[path[0]] || {}
            colors[path[0]][path[1]] = colors[path[0]][path[1]] || {}
            colors[path[0]][path[1]][path[2]] = input.value
        })
        return colors
    }

    function render(form) {
        var style = form.querySelector('[data-toast-preview-style]')
        if (style) style.textContent = toastColorsCss(read(form), PREVIEW_SCOPES)

        form.querySelectorAll('[data-toast-toggle]').forEach(function (toggle) {
            var part = toggle.getAttribute('data-toast-toggle')
            var checked = toggle.checked
            if (part === 'border') {
                form.querySelectorAll('[data-toast-preview] [data-laravel-toast]').forEach(function (el) {
                    el.style.borderWidth = checked ? '' : '0'
                })
                return
            }
            form.querySelectorAll('[data-toast-preview] [data-toast-part="' + part + '"]').forEach(function (el) {
                el.style.display = checked ? '' : 'none'
            })
        })

        var opacity = form.querySelector('[data-toast-opacity]')
        if (opacity) form.querySelectorAll('[data-toast-preview] [data-laravel-toast]').forEach(function (el) {
            el.style.opacity = opacity.value
        })
    }

    function sync(input) {
        var row = input.closest('[data-toast-color-row]')
        var hidden = row.querySelector('[data-toast-color-value]')
        var label = row.querySelector('[data-toast-color-label]')
        hidden.value = input.value
        label.textContent = input.value
        row.setAttribute('data-custom', 'true')
    }

    function clear(button) {
        var row = button.closest('[data-toast-color-row]')
        row.querySelector('[data-toast-color-value]').value = ''
        row.querySelector('[data-toast-color-label]').textContent = row.getAttribute('data-default-label')
        row.removeAttribute('data-custom')
    }

    root.initToastSettings = function () {
        document.querySelectorAll('[data-toast-settings]').forEach(function (form) {
            if (form.getAttribute('data-ready')) return
            form.setAttribute('data-ready', 'true')

            form.addEventListener('input', function (event) {
                if (event.target.matches('[data-toast-color-input]')) sync(event.target)
                render(form)
            })
            form.addEventListener('click', function (event) {
                var confirmation = event.target.closest('[data-toast-confirm]')
                if (confirmation && !window.confirm(confirmation.getAttribute('data-toast-confirm'))) event.preventDefault()
                var button = event.target.closest('[data-toast-color-clear]')
                if (!button) return
                clear(button)
                render(form)
            })
            render(form)
        })
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', root.initToastSettings)
    else root.initToastSettings()
})(window)
