# Notification settings

The settings feature lets people with the right permission change how notifications look and behave from a browser, with a live preview, and saves the result to your database. It is optional. Nothing is created, exposed or changed until you turn it on, and Composer updates never enable it.

What can be changed:

- **Colors** for each notification type (success, error, warning, info), separately for light and dark mode: background, text, border, icon, progress bar and progress track. A part left on Default keeps the CSS framework's own color.
- **Behavior**: position, text direction, duration, maximum visible, opacity, enter and exit animations and durations, progress direction and position, and the auto dismiss, pause on hover, stack, icon, border, close button, progress bar and flash conversion switches.

Saved values override `config/toast.php` and `.env` while the feature is enabled. Anything never saved falls back to config.

## Turn it on

The install and update commands do each step below for you:

```bash
php artisan toast:install --settings
php artisan toast:update --settings
```

Add `--settings-page` to also publish a full page, and `--settings-layout=layouts.app` to render that page inside your application layout. See [Install and update options](#install-and-update-options).

By hand:

```bash
php artisan vendor:publish --tag=toast-settings-migrations
php artisan migrate
```

```dotenv
TOAST_SETTINGS_ENABLED=true
```

Review the published migration before running it. It creates one table (`toast_settings` by default) on the connection named by `TOAST_SETTINGS_CONNECTION`, or your default connection when that is empty. It never touches the users table.

## Authorize who can change settings

Define the gate named by `TOAST_SETTINGS_GATE` (default `manage-toast-settings`) in a service provider. It should check an administrator permission or policy your application already has:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('manage-toast-settings', fn ($user) => $user->can('manage-site'));
```

An undefined gate denies everyone, so enabling the feature never opens it by accident. Every route also runs the middleware list in `TOAST_SETTINGS_MIDDLEWARE` (default `web,auth`) before the gate. Add your own, for example `web,auth,verified,can:admin`.

Until the migration has run, the panel is readable with disabled buttons and writes answer `409`.

## Add the UI

Pick one of the following. All of them talk to the same routes and use the same gate.

### Blade

```blade
@toastSettings
```

or

```blade
@include('toast::settings')
```

The panel is a regular form that posts to the package routes and redirects back with a toast. A small inline script powers the color pickers, the live preview and the per-color Default buttons; the behavior options work without it. The panel picks the Tailwind, Bootstrap 5 or Bootstrap 4 markup from your CSS framework setting.

### Livewire

```blade
<livewire:toast-settings />
```

Save and Reset use Livewire actions, and a toast confirms the result through the `toast-success` and `toast-error` events, so keep `<livewire:toast-container />` in your layout.

### Vue, React and Svelte

```vue
<script setup>
import ToastSettings from '../../../vendor/jeremykenedy/laravel-toast/resources/js/vue/pages/ToastSettings.vue'
</script>

<template>
    <ToastSettings endpoint="/toast/settings" />
</template>
```

```jsx
import ToastSettings from '../../../vendor/jeremykenedy/laravel-toast/resources/js/react/pages/ToastSettings.jsx'

export default function Page() {
    return <ToastSettings endpoint="/toast/settings" />
}
```

```svelte
<script>
    import ToastSettings from '../../../vendor/jeremykenedy/laravel-toast/resources/js/svelte/pages/ToastSettings.svelte'
</script>

<ToastSettings endpoint="/toast/settings" />
```

| Prop | Default | Description |
|------|---------|-------------|
| `endpoint` | `/toast/settings` | Base URL of the settings routes. Data loads from `{endpoint}/data`; saving and resetting use `{endpoint}`. |
| `csrf` | meta tag | CSRF token. Read from `<meta name="csrf-token">` when omitted. |
| `initial` | `null` | Settings payload from the server, to skip the first request. Same shape as the `data` response. |
| `labels` | English | Object that overrides any label. Keys match the `settings` block in `resources/lang/en/toast.php`. |

The components import `resources/css/toast-settings.css`, which is plain CSS with no CSS framework classes, so they look correct in Tailwind, Bootstrap 5 and Bootstrap 4 applications. Dark mode follows `.dark` or `data-bs-theme="dark"` on an ancestor.

### A full page

```bash
php artisan toast:install --settings-page --settings-layout=layouts.app --settings-section=content
```

This publishes `resources/views/toast/settings.blade.php`, sets `TOAST_SETTINGS_PAGE=true` and `TOAST_SETTINGS_VIEW=toast.settings`, and registers `GET /toast/settings`. The published view extends the layout in `TOAST_SETTINGS_LAYOUT` and fills the section named by `TOAST_SETTINGS_SECTION`, so it sits inside your own navigation and styling. Edit the published file freely. With no layout set, the package renders a standalone page that loads Tailwind or Bootstrap from a CDN; set a layout for production.

## Install and update options

Both `toast:install` and `toast:update` accept these flags. Without any of them, an interactive run asks whether to add the settings page and walks through the choices. With `--no-interaction`, nothing about settings changes unless a flag is given.

| Flag | Description |
|------|-------------|
| `--settings` | Enable settings, publish the migration once, and print the next steps. |
| `--settings-page` | Also publish the page view and register the page route. |
| `--settings-layout=` | Blade layout the page extends, for example `layouts.app`. The view must exist. |
| `--settings-section=` | Section the layout yields. Default `content`. |
| `--settings-middleware=` | Comma separated route middleware. Default `web,auth`. |
| `--settings-gate=` | Gate name. Default `manage-toast-settings`. |

Options are validated before any file is written. The commands never run migrations and never define the gate for you. `toast:update --settings` does not ask for frameworks.

## Configuration

| Config key | Environment variable | Default |
|------------|----------------------|---------|
| `settings.enabled` | `TOAST_SETTINGS_ENABLED` | `false` |
| `settings.gate` | `TOAST_SETTINGS_GATE` | `manage-toast-settings` |
| `settings.connection` | `TOAST_SETTINGS_CONNECTION` | `null`, the default connection |
| `settings.table` | `TOAST_SETTINGS_TABLE` | `toast_settings` |
| `settings.prefix` | `TOAST_SETTINGS_PREFIX` | `toast/settings` |
| `settings.name` | Config only | `toast.settings.` |
| `settings.middleware` | `TOAST_SETTINGS_MIDDLEWARE` | `web,auth` |
| `settings.page` | `TOAST_SETTINGS_PAGE` | `false` |
| `settings.view` | `TOAST_SETTINGS_VIEW` | `null`, the package page |
| `settings.layout` | `TOAST_SETTINGS_LAYOUT` | `null`, a standalone page |
| `settings.section` | `TOAST_SETTINGS_SECTION` | `content` |
| `colors` | Config only | `[]` |

### Colors in config

Colors can also be set without the UI, which is useful for a fixed brand palette. Every part is optional and values must be hex:

```php
'colors' => [
    'success' => [
        'light' => ['background' => '#ecfdf5', 'text' => '#065f46', 'icon' => '#10b981'],
        'dark'  => ['background' => '#064e3b', 'text' => '#d1fae5'],
    ],
],
```

Types are `success`, `error`, `warning` and `info`. Modes are `light` and `dark`. Parts are `background`, `text`, `border`, `icon`, `progress` and `track`. Colors saved from the settings page replace this array while the feature is enabled.

## Routes

| Method | URI | Name | Purpose |
|--------|-----|------|---------|
| `GET` | `/toast/settings` | `toast.settings.index` | Full page. Registered only when `settings.page` is true. |
| `GET` | `/toast/settings/data` | `toast.settings.show` | Current values, defaults, field definitions and URLs as JSON. |
| `PUT` | `/toast/settings` | `toast.settings.update` | Save. Returns JSON for JSON requests, otherwise redirects back with a toast. |
| `DELETE` | `/toast/settings` | `toast.settings.destroy` | Remove saved settings and return to config defaults. |

All four run `settings.middleware` and then the gate. The prefix is `settings.prefix`.

### Request format

```json
{
    "options": { "position": "bottom-right", "duration": 8000, "show_close": false },
    "colors": {
        "success": { "light": { "background": "#ecfdf5", "text": null } }
    }
}
```

Send `null` or an empty string for a color to return it to Default. When `colors` is present it replaces every saved color, so send the full set. When it is omitted, saved colors are kept; the same applies to `options`. Unknown keys and invalid values are rejected with `422`.

## How colors are applied

Every renderer marks its markup with `data-toast-type` and `data-toast-part` (`icon`, `track`, `bar`). One generated stylesheet targets those markers, so Blade, Livewire, Vue, React and Svelte are recolored the same way in every CSS framework. Only parts with a color set produce a rule.

- Blade and Livewire write the stylesheet into the page next to the animation styles.
- Vue, React and Svelte receive it as `colors_css` on each toast and inject one `<style id="toast-colors">` tag, so Inertia and broadcast toasts are covered too.
- Light rules apply by default. Dark rules apply under `.dark` or `[data-bs-theme="dark"]`, the same ancestors the package's dark styles use.
- Setting a text color also makes the Bootstrap close button follow it, because Bootstrap draws that button in a fixed color.
- Values are validated as `#rgb` or `#rrggbb` before they are stored or written to CSS.

## Security notes

- Disabled by default, and an undefined gate denies everyone.
- Updates go through a form request that authorizes with the gate and validates every key; only known types, modes, parts and options are accepted.
- Saved settings live in your database and are cached with the application cache. Saving and resetting clear that cache entry.
- The standalone page loads CSS from a public CDN. Use your own layout in production.

## Customizing the views

```bash
php artisan vendor:publish --tag=toast-views
```

publishes the Blade and Livewire panels with the rest of the views. Panels are `settings.blade.php` in each CSS framework's `blade` directory and `toast-settings.blade.php` under `livewire`. The preview toasts use the same classes as real toasts, so published overrides to the toast markup should be mirrored there.

Translations for the panel are in the `settings` block of `resources/lang/{locale}/toast.php`. English ships; other locales fall back to English until translated.
