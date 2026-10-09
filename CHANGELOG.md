# Changelog

All notable changes to `jeremykenedy/laravel-toast` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v3.1.0] - 2026-10-09

Adds an optional notification settings page with color pickers. Nothing changes
for existing applications until they enable it: the feature is off by default,
no table is created, no routes are registered, and toasts look exactly as
before when no color is configured.

### Added

- Color overrides for each toast type (`success`, `error`, `warning`, `info`) in light and
  dark mode: background, text, border, icon, progress bar, and progress track. Set them with
  the new `colors` config key or from the settings page. Only values you set produce CSS.
- Notification settings UI with live light and dark previews for every behavior option and color:
  `@toastSettings` and `@include('toast::settings')` for Blade, `<livewire:toast-settings />`,
  and `ToastSettings` components for Vue, React, and Svelte. Blade and Livewire have Tailwind,
  Bootstrap 5, and Bootstrap 4 views.
- Optional full page at `/toast/settings` that extends your own Blade layout, with configurable
  middleware, section, prefix, and view.
- Saved settings stored in an opt-in `toast_settings` table that overrides config. Routes:
  `GET /toast/settings/data`, `PUT /toast/settings`, `DELETE /toast/settings`, and
  `GET /toast/settings` when the page is enabled.
- Authorization through the `manage-toast-settings` gate (name configurable). An undefined
  gate denies everyone.
- `toast:install` and `toast:update` flags: `--settings`, `--settings-page`, `--settings-layout`,
  `--settings-section`, `--settings-middleware`, and `--settings-gate`. Interactive runs ask
  whether to add the settings page.
- Publish tags `toast-settings-migrations` and `toast-settings-page`.
- Config keys `colors` and `settings.*`, and environment variables `TOAST_SETTINGS_*`.
- `docs/settings.md` and settings page screenshots.

### Changed

- Every renderer (Blade, Livewire, Vue, React, Svelte, in all three CSS frameworks) marks toasts
  with `data-toast-type` and the icon, track, and bar with `data-toast-part`. Styling is unchanged.
- Toast payloads include `colors_css`, an empty string when no colors are set. JavaScript
  components inject it as one `<style id="toast-colors">` tag.
- `resources/js/toast-options.js` exports `applyToastColors`.

### Upgrade notes

- No action is required to keep your current behavior.
- If you published the toast views (`toast-views`), merge the new `data-toast-type` and
  `data-toast-part` attributes to make colors apply to your overrides. Without them, colors you
  configure will not reach the published markup.
- To use the settings page, run `php artisan toast:update --settings` (add `--settings-page` and
  `--settings-layout=layouts.app` for a page), review and run the published migration, and define
  the `manage-toast-settings` gate. The commands never run migrations or define the gate.

## [v3.0.1] - 2026-09-22

### Added

- README screenshots and a package file tree.

### Fixed

- Frontend dependency compatibility and security updates.

## [v3.0.0] - 2026-09-22

Applications with published views should merge the updated templates to receive
the dismissal and theme fixes.

### Fixed

- SPA containers accept later notification props and retain dismissed IDs until unmount.
- Replacing a stack discards superseded notifications across positions in every renderer.
- Toast payloads include the resolved position, stacking preference, and CSS framework.
- Converted flash messages display once and ignore non-string values consistently.
- Framework commands update an existing toast override when ui-kit is also installed.
- Bootstrap dark colors are scoped to package toasts and respect explicit light mode.
- Livewire manual dismissal runs the exit animation before removing server state.
- Bootstrap and Livewire pause/resume cancel pending frames to avoid duplicate countdowns.
- SPA unmount and replacement cancel both countdowns and pending exit callbacks.
- Installation documents Tailwind source registration and reports the required source path.

### Added

- Bootstrap 4 and Bootstrap 5 styles for Vue, React, and Svelte components.
- Optional queued private broadcasts with explicit recipients, Livewire subscriptions,
  and Echo subscriptions for JavaScript components.
- Mounted component tests, executable timer tests, and Chromium checks for all nine
  SPA/CSS pairings and Bootstrap theme isolation.
- CI selections for Laravel 10 through 13, Livewire 3 and 4, and PHP 8.5.

## [v2.0.0] - 2026-09-11

No breaking changes to the public API. Every public method, config key, view
name, published path, `data-` attribute and container id from v1.0.0 behaves as
it did before, and the toast payload keys are identical.

The Tailwind renderers do emit different utility classes, because that is what
the visual refresh is: `rounded-lg` became `rounded-xl` and `flex-shrink-0`
became `shrink-0`. Structural hooks are untouched, so `.toast-progress-bar`,
`[data-toast-id]`, `#toast-container-*`, `.text-bg-*` and `.alert-*` all still
match. An application that styled against a Tailwind utility token in the
packaged markup should publish the views and keep its own copy.

### Fixed

- Vue, React and Svelte components imported `__` from `@/i18n/translator`. The
  alias does not exist in this package and the symbol was never used, so the
  import broke the bundler build in any application that did not happen to
  define that alias.
- Animations only worked in Blade. The Livewire view shipped 13 of the 49
  keyframe sets and the JavaScript components shipped none, so `enter_animation`
  and `exit_animation` silently did nothing outside Blade. All 49 now work in
  all 15 combinations.
- The Livewire view rendered Tailwind markup regardless of the configured CSS
  framework. Bootstrap 5 and Bootstrap 4 now have their own Livewire views.
- Livewire auto-dismiss timers were bound on `DOMContentLoaded` only, so a toast
  dispatched after first paint never started its countdown or progress bar.
- The Bootstrap 4 close button relied on jQuery through `data-dismiss="alert"`,
  and the Bootstrap 5 close button relied on the Bootstrap JS bundle. Both now
  close on their own, and still run the configured exit animation.
- The React progress bar never moved. Progress was held in a ref, which updates
  without triggering a render. Pausing on hover also never resumed.
- The Svelte progress bar jumped back to full when the pointer left a paused
  toast instead of resuming from where it stopped.
- The fixed toast container swallowed clicks in its column, including the gaps
  between toasts. It now passes clicks through to the page underneath.
- `vendor:publish --tag=toast-views` wrote to
  `resources/views/vendor/toast/{framework}/blade`, a path the view finder never
  looked at, so published overrides were ignored. That path now resolves.
- `Livewire::component()` was called whenever the class was autoloadable, which
  threw when the Livewire service provider had not been registered.
- The Livewire component never applied `max_visible`, so dispatching in a loop
  grew the stack without bound.
- Bootstrap 4, Bootstrap 5 and Livewire turned a `duration` of 0 into a five
  second countdown, so a toast documented as manual-dismiss-only disappeared on
  its own. Tailwind, Vue, React and Svelte already honoured it.
- Hover and focus shared one pause flag, so moving focus off the close button
  restarted the countdown while the pointer was still over the toast, and the
  later mouseleave could start a second timer loop.
- `role="alert"` carries an implicit assertive live region, so every toast
  interrupted the screen reader regardless of type. Politeness is now stated
  outright in each renderer.
- A Livewire toast that auto-dismissed was removed from the DOM but left in the
  component's `$toasts`, so the next morph rendered it again with a fresh timer.
- The Livewire morph hook was only registered inside the `livewire:initialized`
  listener. When the container first renders with a later toast that event has
  already fired, so no hook was attached and subsequent toasts got no timers.
- `stack` and each payload's own `position` were ignored by the Vue, React and
  Svelte components. They rendered one flat list from a single position prop,
  while Blade and Livewire grouped by position and applied the stack rule. All
  three now group the same way.
- The Livewire timer marked bound elements with a `data-` attribute, which the
  next morph could strip, letting a second timer attach to the same toast. It
  uses a `WeakSet` now.
- The Livewire timer script only rendered alongside the first toast. A script
  morphed into the DOM never executes, so that toast started without a timer.
  It renders on every pass, including the empty one.
- A Livewire toast dismissed on a timer resolved its component after the exit
  animation, by which point a morph could have detached the node, leaving the
  toast in `$toasts`. The component is captured before the animation starts.
- A Livewire toast awaiting its dismiss round trip kept `pointer-events: auto`
  while invisible, so a slow response left it swallowing clicks.
- The Livewire progress bars lost the `data-duration` attribute v1 shipped,
  which consumers query as `.toast-progress-bar[data-duration]`. Restored.
- A published pre-parity `livewire/toast-container.blade.php` override, which is
  Tailwind only, resolved ahead of the packaged Bootstrap views, so upgraded
  Bootstrap installs kept rendering Tailwind markup.
- `max_visible` passed per toast was stored in the payload but never applied;
  trimming always read the global config.
- Tailwind focus ring colours had no `dark:` counterpart, unlike every other
  colour utility in those views.
- The React mount effect captured `dismiss` while the toast list was still
  empty, so every toast skipped its exit animation.
- React `resume` started an animation frame inside a `setProgress` updater.
  Strict Mode invokes updaters twice, which started two competing timer loops.
- The Bootstrap close and timer listeners were page wide. `.toast.show
  .btn-close` matches every Bootstrap 5 toast on the page, so clicking a host
  application's own toast ran this package's dismiss and removed it. Every
  listener is now scoped to a `data-laravel-toast` marker, valued `blade` or
  `livewire` so the two renderers never bind each other's toasts.
- `toast:install` and `toast:switch` wrote `TOAST_CSS` alongside `UI_KIT_CSS`.
  Since `toast.css_framework` takes precedence, that pinned toast and stopped a
  later kit wide switch from moving it. In a ui-kit application only the
  `UI_KIT_*` value is written now, matching v1.0.0.
- `toast:install --no-interaction` validated its options only when both `--css`
  and `--frontend` were given, so a single invalid value was written to `.env`
  and reported as a success.
- A `toast-js` publish tag added during this cycle copied the components to
  `resources/js/vendor/toast`, where their stylesheet import resolved to
  `resources/js/vendor/css` and failed the bundler. The tag is gone; copy the
  component out of `vendor/` instead, which keeps the import correct.

### Added

- `toast.css_framework` and `toast.frontend` config keys, with `TOAST_CSS` and
  `TOAST_FRONTEND` env support, so the package resolves views without
  `jeremykenedy/laravel-ui-kit` installed. Both default to null and defer to
  `ui-kit.*`, so existing ui-kit applications are unaffected.
- `ToastManager::build()`, which produces a toast payload without touching the
  session. The Livewire component now builds through it, so defaults resolve in
  one place.
- `Jeremykenedy\LaravelToast\Support\ToastAnimations`, the single source for the
  keyframes shared by every renderer.
- `resources/css/toast-animations.css`, published with `--tag=toast-css`.
- `prefers-reduced-motion` support. Toasts still appear and dismiss on schedule,
  they just stop moving. The rule is scoped to the package's own attributes, so
  it cannot reach a host element whose id happens to start with `toast-`.
- Keyboard focus pauses the countdown, matching the existing hover behavior.
- `dismissLabel` prop on the Vue, React and Svelte components, so the close
  button can be translated.
- `data-laravel-toast` on every toast the package renders, so its own scripts
  can tell its markup apart from the host application's.
- Composer scripts: `composer test`, `composer lint`, `composer format`.

### Changed

- Visual refresh across all five frontends: larger corner radius, layered shadow
  and ring, tighter type, and a width that fits a phone screen. Every class
  hook, selector, data attribute and container id is unchanged.
- Error toasts announce as `assertive`, other types as `polite`. Decorative
  icons are hidden from assistive technology. Focus rings use `focus-visible`.
- `php` constraint simplified from `^8.2|^8.3` to the equivalent `^8.2`.
- `livewire/livewire` added to `require-dev` so the Livewire component is
  covered by tests. It is not a runtime dependency.
- The README framework matrix now reflects what actually ships styled. The Vue,
  React and Svelte components carry a single Tailwind class map, so pairing them
  with Bootstrap gives correct behavior with Tailwind markup. That was true
  before this release too; the matrix simply claimed otherwise.

### Testing and CI

- Suite grew from 164 to 323 tests.
- New jobs: install without Livewire, `composer validate`, framework isolation
  (no Bootstrap classes in Tailwind views, no Alpine in Livewire views, and so
  on), and frontend checks that reject application path aliases.

## [v1.0.0] - 2026-04-01

Initial release.

[v2.0.0]: https://github.com/jeremykenedy/laravel-toast/compare/v1.0.0...v2.0.0
[v1.0.0]: https://github.com/jeremykenedy/laravel-toast/releases/tag/v1.0.0
