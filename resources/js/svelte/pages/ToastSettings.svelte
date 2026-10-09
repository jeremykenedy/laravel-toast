<script>
    import { onMount } from 'svelte'
    import '../../../css/toast-settings.css'
    import { TOAST_MODES } from '../../toast-colors.js'
    import {
        SAMPLE_ICON_PATHS, colorMatrix, colorsBody, csrfToken, errorMessage, loadPayload, mergeLabels,
        optionsBody, previewCss, setColor, settingsRequest,
    } from '../../toast-settings-shared.js'

    export let endpoint = '/toast/settings'
    export let csrf = ''
    export let initial = null
    export let labels = {}

    const modes = TOAST_MODES
    let payload = initial
    let options = { ...(initial?.options || {}) }
    let colors = colorMatrix(initial?.colors)
    let busy = false
    let status = null

    $: text = mergeLabels(labels)
    $: ready = payload?.ready !== false
    $: css = previewCss(colors, options)

    function adopt(data) {
        payload = { ...(payload || {}), ...data }
        options = { ...data.options }
        colors = colorMatrix(data.colors)
    }

    async function send(method, body, message) {
        busy = true
        status = null
        try {
            const result = await settingsRequest(endpoint, method, csrfToken(csrf), body)
            if (result.ok) { adopt(result.data); status = { type: 'success', message } }
            else status = { type: 'error', message: errorMessage(result, text) }
        } catch {
            status = { type: 'error', message: text.request_failed }
        } finally {
            busy = false
        }
    }

    function save() { return send('PUT', { options: optionsBody(options, payload.fields), colors: colorsBody(colors) }, text.saved_message) }
    function reset() { return send('DELETE', undefined, text.reset_message) }
    function pick(type, mode, part, value) { colors = setColor(colors, type, mode, part, value) }
    function readField(key, field, event) {
        options = { ...options, [key]: field.type === 'checkbox' ? event.target.checked : event.target.value }
    }

    onMount(async () => {
        if (payload) return
        const result = await loadPayload(endpoint)
        if (result.ok) adopt(result.data)
        else status = { type: 'error', message: errorMessage(result, text) }
    })
</script>

<div data-toast-settings-root aria-busy={payload ? 'false' : 'true'}>
    <div class="ts-header">
        <h2>{text.title}</h2>
        <p class="ts-muted">{text.description}</p>
    </div>

    {#if !payload && !status}<p class="ts-muted">{text.loading}</p>{/if}
    {#if !payload && status}<p class="ts-status" data-status={status.type} role="alert">{status.message}</p>{/if}

    {#if payload}
        {#if !ready}<div class="ts-notice" role="alert">{text.not_ready}</div>{/if}

        <form on:submit|preventDefault={save}>
            <section class="ts-section" aria-labelledby="toast-settings-colors">
                <h3 id="toast-settings-colors">{text.colors}</h3>
                <p class="ts-muted">{text.colors_help}</p>

                {#each payload.types as type (type)}
                    <div class="ts-type">
                        <h4>{text.types[type]}</h4>
                        <div class="ts-grid">
                            {#each modes as mode (mode)}
                                <div>
                                    <p class="ts-mode">{text[mode]}</p>
                                    {#each payload.parts as part (part)}
                                        <div class="ts-row" data-toast-color-row>
                                            <span class="ts-row-label">{text.parts[part]}</span>
                                            <input type="color" value={colors[type][mode][part] || '#888888'} aria-label="{text.types[type]} {text[mode]} {text.parts[part]}" data-color="{type}.{mode}.{part}" on:input={event => pick(type, mode, part, event.currentTarget.value)}>
                                            <span class="ts-hex">{colors[type][mode][part] || text.default}</span>
                                            <button type="button" class="ts-link" aria-label="{text.default}: {text.types[type]} {text[mode]} {text.parts[part]}" on:click={() => pick(type, mode, part, '')}>{text.default}</button>
                                        </div>
                                    {/each}
                                </div>
                            {/each}
                        </div>
                    </div>
                {/each}
            </section>

            <section class="ts-section" aria-labelledby="toast-settings-preview">
                <h3 id="toast-settings-preview">{text.preview}</h3>
                {@html '<style data-toast-preview-style>' + css + '</style>'}
                <div class="ts-grid" style="margin-top:1rem;">
                    {#each modes as mode (mode)}
                        <div data-toast-preview={mode} class:dark={mode === 'dark'} data-bs-theme={mode}>
                            <p class="ts-mode">{text[mode]}</p>
                            {#each payload.types as type (type)}
                                <div data-laravel-toast="preview" data-toast-type={type} role="presentation">
                                    <div data-toast-part="track" class="ts-track"><div data-toast-part="bar" class="ts-bar"></div></div>
                                    <div class="ts-body">
                                        <span data-toast-part="icon" class="ts-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d={SAMPLE_ICON_PATHS[type]} /></svg></span>
                                        <div class="ts-content">
                                            <p class="ts-title">{text.types[type]}</p>
                                            <p>{text.sample_message}</p>
                                        </div>
                                        <span data-toast-part="close" class="ts-close" aria-hidden="true">&times;</span>
                                    </div>
                                </div>
                            {/each}
                        </div>
                    {/each}
                </div>
            </section>

            <section class="ts-section" aria-labelledby="toast-settings-options">
                <h3 id="toast-settings-options">{text.options}</h3>
                <div class="ts-fields">
                    {#each Object.entries(payload.fields) as [key, field] (key)}
                        {#if field.type === 'checkbox'}
                            <label class="ts-check">
                                <input type="checkbox" name={key} checked={Boolean(options[key])} on:change={event => readField(key, field, event)}>
                                <span>{text.fields[key] || key}</span>
                            </label>
                        {:else}
                            <label class="ts-field">
                                <span>{text.fields[key] || key}</span>
                                {#if field.type === 'select'}
                                    <select name={key} value={options[key]} on:change={event => readField(key, field, event)}>
                                        {#each field.options as option (option)}<option value={option} selected={option === options[key]}>{option}</option>{/each}
                                    </select>
                                {:else}
                                    <input type="number" name={key} value={options[key] ?? ''} min={field.min} max={field.max} step={field.step} on:input={event => readField(key, field, event)}>
                                {/if}
                            </label>
                        {/if}
                    {/each}
                </div>
            </section>

            <div class="ts-actions">
                <button type="submit" class="ts-primary" disabled={!ready || busy}>{busy ? text.saving : text.save}</button>
                <button type="button" class="ts-secondary" disabled={!ready || busy} on:click={reset}>{text.reset}</button>
                {#if status}<span class="ts-status" data-status={status.type} role="status">{status.message}</span>{/if}
            </div>
        </form>
    {/if}
</div>
