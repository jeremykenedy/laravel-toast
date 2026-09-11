# Security Policy

## Supported versions

| Version | Supported |
|---------|-----------|
| 1.x     | Yes       |
| < 1.0   | No        |

## Reporting a vulnerability

Email jeremykenedy@gmail.com. Do not open a public issue.

Include the affected version, what an attacker can do, and the steps to
reproduce it. You will get an acknowledgement within a few days, and a fix or an
explanation of why it is not an issue once it has been looked at.

## Scope

This package renders notification markup from data the host application passes
it. Two things are worth knowing:

- `message` and `title` are escaped by the views.
- `custom_icon` is rendered as raw HTML, because it exists to accept an SVG.
  Never build it from user input.

The same applies to `custom_icon` in the Vue, React and Svelte components, which
render it through `v-html`, `dangerouslySetInnerHTML` and `{@html}`.
