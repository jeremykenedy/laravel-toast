import React, { useState, useEffect, useMemo } from 'react'
import '../../../css/toast-settings.css'
import { TOAST_MODES } from '../../toast-colors.js'
import {
    SAMPLE_ICON_PATHS, colorMatrix, colorsBody, csrfToken, errorMessage, loadPayload, mergeLabels,
    optionsBody, previewCss, setColor, settingsRequest,
} from '../../toast-settings-shared.js'

export default function ToastSettings({ endpoint = '/toast/settings', csrf = '', initial = null, labels = {} }) {
    const text = useMemo(() => mergeLabels(labels), [labels])
    const [payload, setPayload] = useState(initial)
    const [options, setOptions] = useState({ ...(initial?.options || {}) })
    const [colors, setColors] = useState(() => colorMatrix(initial?.colors))
    const [busy, setBusy] = useState(false)
    const [status, setStatus] = useState(null)

    const ready = payload?.ready !== false
    const css = useMemo(() => previewCss(colors, options), [colors, options])

    function adopt(data) {
        setPayload(current => ({ ...(current || {}), ...data }))
        setOptions({ ...data.options })
        setColors(colorMatrix(data.colors))
    }

    useEffect(() => {
        if (payload) return
        let active = true
        loadPayload(endpoint).then(result => {
            if (!active) return
            if (result.ok) adopt(result.data)
            else setStatus({ type: 'error', message: errorMessage(result, text) })
        })
        return () => { active = false }
    }, [])

    async function send(method, body, message) {
        setBusy(true)
        setStatus(null)
        try {
            const result = await settingsRequest(endpoint, method, csrfToken(csrf), body)
            if (result.ok) { adopt(result.data); setStatus({ type: 'success', message }) }
            else setStatus({ type: 'error', message: errorMessage(result, text) })
        } catch {
            setStatus({ type: 'error', message: text.request_failed })
        } finally {
            setBusy(false)
        }
    }

    const save = event => {
        event.preventDefault()
        send('PUT', { options: optionsBody(options, payload.fields), colors: colorsBody(colors) }, text.saved_message)
    }
    const reset = () => send('DELETE', undefined, text.reset_message)
    const pick = (type, mode, part, value) => setColors(current => setColor(current, type, mode, part, value))
    const readField = (key, field, event) => setOptions(current => ({ ...current, [key]: field.type === 'checkbox' ? event.target.checked : event.target.value }))

    return (
        <div data-toast-settings-root aria-busy={payload ? 'false' : 'true'}>
            <div className="ts-header">
                <h2>{text.title}</h2>
                <p className="ts-muted">{text.description}</p>
            </div>

            {!payload && !status && <p className="ts-muted">{text.loading}</p>}
            {!payload && status && <p className="ts-status" data-status={status.type} role="alert">{status.message}</p>}

            {payload && (
                <>
                    {!ready && <div className="ts-notice" role="alert">{text.not_ready}</div>}

                    <form onSubmit={save}>
                        <section className="ts-section" aria-labelledby="toast-settings-colors">
                            <h3 id="toast-settings-colors">{text.colors}</h3>
                            <p className="ts-muted">{text.colors_help}</p>

                            {payload.types.map(type => (
                                <div key={type} className="ts-type">
                                    <h4>{text.types[type]}</h4>
                                    <div className="ts-grid">
                                        {TOAST_MODES.map(mode => (
                                            <div key={mode}>
                                                <p className="ts-mode">{text[mode]}</p>
                                                {payload.parts.map(part => (
                                                    <div key={part} className="ts-row" data-toast-color-row>
                                                        <span className="ts-row-label">{text.parts[part]}</span>
                                                        <input type="color" value={colors[type][mode][part] || '#888888'} aria-label={`${text.types[type]} ${text[mode]} ${text.parts[part]}`} data-color={`${type}.${mode}.${part}`} onChange={event => pick(type, mode, part, event.target.value)} />
                                                        <span className="ts-hex">{colors[type][mode][part] || text.default}</span>
                                                        <button type="button" className="ts-link" aria-label={`${text.default}: ${text.types[type]} ${text[mode]} ${text.parts[part]}`} onClick={() => pick(type, mode, part, '')}>{text.default}</button>
                                                    </div>
                                                ))}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </section>

                        <section className="ts-section" aria-labelledby="toast-settings-preview">
                            <h3 id="toast-settings-preview">{text.preview}</h3>
                            <style data-toast-preview-style>{css}</style>
                            <div className="ts-grid" style={{ marginTop: '1rem' }}>
                                {TOAST_MODES.map(mode => (
                                    <div key={mode} data-toast-preview={mode} className={mode === 'dark' ? 'dark' : undefined} data-bs-theme={mode}>
                                        <p className="ts-mode">{text[mode]}</p>
                                        {payload.types.map(type => (
                                            <div key={type} data-laravel-toast="preview" data-toast-type={type} role="presentation">
                                                <div data-toast-part="track" className="ts-track"><div data-toast-part="bar" className="ts-bar" /></div>
                                                <div className="ts-body">
                                                    <span data-toast-part="icon" className="ts-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true"><path strokeLinecap="round" strokeLinejoin="round" d={SAMPLE_ICON_PATHS[type]} /></svg></span>
                                                    <div className="ts-content">
                                                        <p className="ts-title">{text.types[type]}</p>
                                                        <p>{text.sample_message}</p>
                                                    </div>
                                                    <span data-toast-part="close" className="ts-close" aria-hidden="true">&times;</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section className="ts-section" aria-labelledby="toast-settings-options">
                            <h3 id="toast-settings-options">{text.options}</h3>
                            <div className="ts-fields">
                                {Object.entries(payload.fields).map(([key, field]) => field.type === 'checkbox' ? (
                                    <label key={key} className="ts-check">
                                        <input type="checkbox" name={key} checked={Boolean(options[key])} onChange={event => readField(key, field, event)} />
                                        <span>{text.fields[key] || key}</span>
                                    </label>
                                ) : (
                                    <label key={key} className="ts-field">
                                        <span>{text.fields[key] || key}</span>
                                        {field.type === 'select' ? (
                                            <select name={key} value={options[key]} onChange={event => readField(key, field, event)}>
                                                {field.options.map(option => <option key={option} value={option}>{option}</option>)}
                                            </select>
                                        ) : (
                                            <input type="number" name={key} value={options[key] ?? ''} min={field.min} max={field.max} step={field.step} onChange={event => readField(key, field, event)} />
                                        )}
                                    </label>
                                ))}
                            </div>
                        </section>

                        <div className="ts-actions">
                            <button type="submit" className="ts-primary" disabled={!ready || busy}>{busy ? text.saving : text.save}</button>
                            <button type="button" className="ts-secondary" disabled={!ready || busy} onClick={reset}>{text.reset}</button>
                            {status && <span className="ts-status" data-status={status.type} role="status">{status.message}</span>}
                        </div>
                    </form>
                </>
            )}
        </div>
    )
}
