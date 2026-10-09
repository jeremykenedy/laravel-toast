<script setup>
import { ref, computed, onMounted } from 'vue'
import '../../../css/toast-settings.css'
import { TOAST_MODES } from '../../toast-colors.js'
import {
    SAMPLE_ICON_PATHS, colorMatrix, colorsBody, csrfToken, errorMessage, loadPayload, mergeLabels,
    optionsBody, previewCss, setColor, settingsRequest,
} from '../../toast-settings-shared.js'

const props = defineProps({
    endpoint: { type: String, default: '/toast/settings' },
    csrf: { type: String, default: '' },
    initial: { type: Object, default: null },
    labels: { type: Object, default: () => ({}) },
})

const text = computed(() => mergeLabels(props.labels))
const payload = ref(props.initial)
const options = ref({ ...(props.initial?.options || {}) })
const colors = ref(colorMatrix(props.initial?.colors))
const busy = ref(false)
const status = ref(null)

const ready = computed(() => payload.value?.ready !== false)
const css = computed(() => previewCss(colors.value, options.value))

function adopt(data) {
    payload.value = { ...(payload.value || {}), ...data }
    options.value = { ...data.options }
    colors.value = colorMatrix(data.colors)
}

async function load() {
    const result = await loadPayload(props.endpoint)
    if (result.ok) adopt(result.data)
    else status.value = { type: 'error', message: errorMessage(result, text.value) }
}

async function send(method, body, message) {
    busy.value = true
    status.value = null
    try {
        const result = await settingsRequest(props.endpoint, method, csrfToken(props.csrf), body)
        if (result.ok) {
            adopt(result.data)
            status.value = { type: 'success', message }
        } else {
            status.value = { type: 'error', message: errorMessage(result, text.value) }
        }
    } catch {
        status.value = { type: 'error', message: text.value.request_failed }
    } finally {
        busy.value = false
    }
}

const save = () => send('PUT', { options: optionsBody(options.value, payload.value.fields), colors: colorsBody(colors.value) }, text.value.saved_message)
const reset = () => send('DELETE', undefined, text.value.reset_message)

function pick(type, mode, part, value) { colors.value = setColor(colors.value, type, mode, part, value) }

function readField(key, field, event) {
    options.value = { ...options.value, [key]: field.type === 'checkbox' ? event.target.checked : event.target.value }
}

onMounted(() => { if (!payload.value) load() })
</script>

<template>
    <div data-toast-settings-root :aria-busy="payload ? 'false' : 'true'">
        <div class="ts-header">
            <h2>{{ text.title }}</h2>
            <p class="ts-muted">{{ text.description }}</p>
        </div>

        <p v-if="!payload && !status" class="ts-muted">{{ text.loading }}</p>

        <template v-if="payload">
            <div v-if="!ready" class="ts-notice" role="alert">{{ text.not_ready }}</div>

            <form @submit.prevent="save">
                <section class="ts-section" aria-labelledby="toast-settings-colors">
                    <h3 id="toast-settings-colors">{{ text.colors }}</h3>
                    <p class="ts-muted">{{ text.colors_help }}</p>

                    <div v-for="type in payload.types" :key="type" class="ts-type">
                        <h4>{{ text.types[type] }}</h4>
                        <div class="ts-grid">
                            <div v-for="mode in TOAST_MODES" :key="mode">
                                <p class="ts-mode">{{ text[mode] }}</p>
                                <div v-for="part in payload.parts" :key="part" class="ts-row" data-toast-color-row>
                                    <span class="ts-row-label">{{ text.parts[part] }}</span>
                                    <input type="color" :value="colors[type][mode][part] || '#888888'" :aria-label="`${text.types[type]} ${text[mode]} ${text.parts[part]}`" :data-color="`${type}.${mode}.${part}`" @input="pick(type, mode, part, $event.target.value)">
                                    <span class="ts-hex">{{ colors[type][mode][part] || text.default }}</span>
                                    <button type="button" class="ts-link" :aria-label="`${text.default}: ${text.types[type]} ${text[mode]} ${text.parts[part]}`" @click="pick(type, mode, part, '')">{{ text.default }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="ts-section" aria-labelledby="toast-settings-preview">
                    <h3 id="toast-settings-preview">{{ text.preview }}</h3>
                    <component :is="'style'" data-toast-preview-style>{{ css }}</component>
                    <div class="ts-grid" style="margin-top:1rem;">
                        <div v-for="mode in TOAST_MODES" :key="mode" :data-toast-preview="mode" :class="{ dark: mode === 'dark' }" :data-bs-theme="mode">
                            <p class="ts-mode">{{ text[mode] }}</p>
                            <div v-for="type in payload.types" :key="type" data-laravel-toast="preview" :data-toast-type="type" role="presentation">
                                <div data-toast-part="track" class="ts-track"><div data-toast-part="bar" class="ts-bar"></div></div>
                                <div class="ts-body">
                                    <span data-toast-part="icon" class="ts-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="SAMPLE_ICON_PATHS[type]"/></svg></span>
                                    <div class="ts-content">
                                        <p class="ts-title">{{ text.types[type] }}</p>
                                        <p>{{ text.sample_message }}</p>
                                    </div>
                                    <span data-toast-part="close" class="ts-close" aria-hidden="true">&times;</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="ts-section" aria-labelledby="toast-settings-options">
                    <h3 id="toast-settings-options">{{ text.options }}</h3>
                    <div class="ts-fields">
                        <template v-for="(field, key) in payload.fields" :key="key">
                            <label v-if="field.type === 'checkbox'" class="ts-check">
                                <input type="checkbox" :name="key" :checked="Boolean(options[key])" @change="readField(key, field, $event)">
                                <span>{{ text.fields[key] || key }}</span>
                            </label>
                            <label v-else class="ts-field">
                                <span>{{ text.fields[key] || key }}</span>
                                <select v-if="field.type === 'select'" :name="key" :value="options[key]" @change="readField(key, field, $event)">
                                    <option v-for="option in field.options" :key="option" :value="option">{{ option }}</option>
                                </select>
                                <input v-else type="number" :name="key" :value="options[key]" :min="field.min" :max="field.max" :step="field.step" @input="readField(key, field, $event)">
                            </label>
                        </template>
                    </div>
                </section>

                <div class="ts-actions">
                    <button type="submit" class="ts-primary" :disabled="!ready || busy">{{ busy ? text.saving : text.save }}</button>
                    <button type="button" class="ts-secondary" :disabled="!ready || busy" @click="reset">{{ text.reset }}</button>
                    <span v-if="status" class="ts-status" :data-status="status.type" role="status">{{ status.message }}</span>
                </div>
            </form>
        </template>
        <p v-else-if="status" class="ts-status" :data-status="status.type" role="alert">{{ status.message }}</p>
    </div>
</template>
