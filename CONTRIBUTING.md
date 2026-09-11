# Contributing

Thanks for taking the time to help.

## Getting set up

```bash
composer install
composer test
```

## Before opening a pull request

```bash
composer format   # Pint, Laravel preset
composer test     # 250 tests
```

Both run in CI, along with `composer validate`, an install without Livewire, and
the framework isolation checks.

## The one rule that matters most

A view file lives in a directory that decides its framework, and it uses only
that framework.

- A Tailwind view has zero Bootstrap classes.
- A Bootstrap view has zero Tailwind utilities.
- A Blade view has zero `wire:` directives. Use Alpine.
- A Livewire view has zero Alpine `x-data`. Use `wire:`.

CI fails the build on any of these.

## Backwards compatibility

This package is installed in a large number of applications. Treat the following
as public API and do not change them without a major version:

- Method signatures on `ToastManager`, the `Toast` facade, the `HasToasts` trait
  and the `toast()` helper
- The keys in a toast payload
- Config keys in `config/toast.php`
- View names (`toast::toasts`, `toast-livewire::toast-container`) and the
  published view paths
- CSS class names, `data-` attributes and container ids that applications style
  or query against

Adding is fine. Renaming and removing is not.

## Animations

`resources/css/toast-animations.css` is the only copy of the keyframes. Blade and
Livewire inline it through `ToastAnimations::styleTag()`, and the JavaScript
components import the file.

To add an animation, add both the `toast-{name}` exit and `toast-enter-{name}`
enter keyframes to that file, then add the name to `ToastAnimations::NAMES`.
`tests/Unit/ToastAnimationsTest.php` fails if the two ever disagree.

## Tests

This is a UI package with no database. Tests must never use `RefreshDatabase`,
factories, seeders, models or database assertions. `tests/TestCase.php` fails the
run if a non-memory connection is configured.

Livewire tests belong in the `livewire` group so the package can still be
verified with Livewire absent.

## Translations

`resources/lang/{locale}/toast.php` needs five keys: `success`, `error`,
`warning`, `info`, `dismiss`. New locales are welcome.
