# Changelog

All notable changes to `jeremykenedy/laravel-toast` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/jeremykenedy/laravel-toast/compare/v1.0.0...HEAD
[v1.0.0]: https://github.com/jeremykenedy/laravel-toast/releases/tag/v1.0.0
