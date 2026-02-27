---
name: i18n-patterns
description: Implement consistent internationalization patterns with i18next-style APIs, plain-English translation keys, and safe interpolation/pluralization boundaries. Use when adding UI copy, form validation messages, conditional text, or i18n runtime wiring.
---

# I18n Patterns

Follow this workflow when adding or updating localized copy.

## 0. Enforce project runtime defaults (required)

When configuring or updating i18n runtime, use the current project conventions.

- Use `i18next` + `react-i18next` only.
- Keep default resource shape as:
  - `resources = { en: { translation: enJson } }`
- Follow the project hybrid key style:
  - plain-English sentence keys for common UI copy where practical,
  - dotted domain keys for structured feature copy (for example `inbox.job.deliveryDue.title`),
  - plural keys as suffix variants (for example `day_one`, `day_other`).
- Keep everything in the single `translation` namespace by default.
- Required config flags:
  - `fallbackLng: "en"`
  - `keySeparator: false`
  - `nsSeparator: false`
  - `returnNull: false`
  - `returnEmptyString: false`
  - `interpolation.escapeValue: false`
  - `react.useSuspense: false`
- Debug flag should be environment-based (for example `import.meta.env.DEV` on web).
- Runtime language should come from platform locale when possible; default to `"en"`.

For this repo, prefer colocated runtime files under `resources/js/i18n/`:
- `resources/js/i18n/config.ts`
- `resources/js/i18n/locales/en.json`

Then import config once in app entry points (app and Storybook preview when stories use translations).

## 1. Define translation boundaries

- Keep translation calls in feature or screen components.
- Keep primitive/base UI components translation-agnostic.
- Pass already-translated strings into primitives when needed.

## 2. Add keys with consistent strategy

- Follow the project hybrid key strategy consistently:
  - sentence keys for straightforward copy,
  - dotted domain keys for grouped feature copy,
  - `_one` / `_other` suffix keys for plurals.
- Keep keys descriptive and stable.
- Avoid dynamic key construction where possible.
- Keep related keys grouped logically in locale files.

## 3. Use interpolation and pluralization correctly

- Use interpolation placeholders for runtime values.
- Use pluralization forms supported by your i18n framework.
- Avoid string concatenation for user-facing sentences.
- Prefer complete sentence templates with variables.

## 4. Handle conditional copy clearly

- Use separate keys for materially different sentences.
- Keep selection logic in code, wording in locale files.
- Ensure each branch maps to an explicit translation key.

## 5. Validate localization safety

- Verify missing-key behavior and fallback language behavior.
- Verify validation/error strings are also localized.
- Verify copy remains understandable when interpolation values are empty/null.

## Practical guardrails

- Do not introduce a new i18n library or translation framework without approval.
- Do not place translation hooks or translation resolution logic in base UI primitives.
- Keep any translation helper usage at route/feature/form boundaries.
- Keep interpolation placeholders explicit and avoid string concatenation.
- Use separate keys for materially different conditional sentences.
- Do not configure `keySeparator`/`nsSeparator` in a way that changes existing key behavior.

## Output checklist

- New copy is fully localizable.
- Key usage matches project hybrid conventions.
- Interpolation/pluralization are implemented correctly.
- No translation logic leaked into primitive components.
- Runtime config matches project defaults.

For strategy details and examples, read `references/i18n.md`.
