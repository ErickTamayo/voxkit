---
name: i18n-patterns
description: Implement consistent internationalization patterns with i18next-style APIs, translation keys, interpolation, and pluralization boundaries. Use when adding UI copy, form validation messages, or conditional text that must remain localizable and maintainable.
---

# I18n Patterns

Follow this workflow when adding or updating localized copy.

## 1. Define translation boundaries

- Keep translation calls in feature or screen components.
- Keep primitive/base UI components translation-agnostic.
- Pass already-translated strings into primitives when needed.

## 2. Add keys with consistent strategy

- Follow one key strategy consistently across the codebase.
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

## Output checklist

- New copy is fully localizable.
- Key usage is consistent with project conventions.
- Interpolation/pluralization are implemented correctly.
- No translation logic leaked into primitive components.

For strategy details and examples, read `references/i18n.md`.
