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

## Libraries and config

- `i18next`
- `react-i18next`
- Config file: `resources/js/i18n/config.ts`

## Locale files

- Primary locale folder: `resources/js/i18n/locales/`
- Current example locale: `resources/js/i18n/locales/en.json`

## Key strategy in Voxspot

- Use plain English sentence keys instead of nested dot-key namespaces.
- Rely on i18next fallback behavior so missing keys display readable text.
- Keep interpolation placeholders in i18next format: `{{variableName}}`.

## Placement rules

- Do not call translation hooks inside base UI primitives.
- Translate in implementation-level components (screens/forms/features).
- Include localized strings in form validation rules.

## Pluralization and conditional copy

- Use `_one` / `_other` entries for pluralizable labels.
- Prefer separate keys for conditional sentences over string building.

## Supporting docs

- `references/i18n.md`

## Output checklist

- New copy is fully localizable.
- Key usage is consistent with project conventions.
- Interpolation/pluralization are implemented correctly.
- No translation logic leaked into primitive components.
