---
name: dto-validation-patterns
description: Implement consistent request validation and typed payload mapping for Laravel REST and GraphQL boundaries. Use when adding or updating request payloads, GraphQL inputs, form validation, or transport-to-domain data mapping.
---

# DTO And Validation Patterns

Follow this workflow when introducing or refactoring validated data flows.

## 1. Validate at transport boundaries

- Use Form Request classes for REST endpoints.
- Use GraphQL `@rules` for GraphQL input validation.
- Keep validation logic out of deep service methods unless it is domain-only validation.

## 2. Keep payload mapping explicit

- Normalize transport payloads before passing to service methods.
- Prefer named helpers/structures over raw nested arrays in deep service logic.
- Keep mapping code deterministic and readable.

## 3. Enforce typed signatures

- Use typed parameters and return types in controllers, services, and mutation classes.
- Avoid `mixed` unless required by framework method contracts.
- Keep nullability explicit.

## 4. Centralize transformations

- Keep repeated parsing/transformation helpers in one place.
- Avoid duplicating string/number/date normalization across files.
- Make conversion rules reusable and testable.

## 5. Validate behavior with tests

- Add/adjust tests for validation pass and fail branches.
- Assert error responses/messages for invalid payloads.
- Assert transformed values where mapping rules are important.

## Output checklist

- Validation is defined at transport boundaries.
- Payload mapping is explicit and reusable.
- Type signatures are clear and consistent.
- Tests cover accepted and rejected payloads.

For repository-specific conventions, read `references/validation-standards.md`.
