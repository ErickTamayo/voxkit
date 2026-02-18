---
name: service-layer-pattern
description: Implement Laravel service-layer workflows with thin transport edges, transactional writes, ownership-safe relationship resolution, and exception-first error handling. Use when adding or refactoring behavior in app/Services, app/Http/Controllers, or app/GraphQL resolvers/mutations.
---

# Service Layer Pattern

Follow this workflow when implementing or refactoring backend business logic.

## 1. Keep transport edges thin

- Keep REST controllers and GraphQL mutation/query classes focused on orchestration.
- Accept validated input, delegate to services, and format responses.
- Do not place multi-step domain logic in controllers or resolvers.

## 2. Define explicit service contracts

- Use typed method parameters and return types.
- Pass normalized input (arrays/DTO-like structures), not Request objects.
- Keep method names behavior-driven (`create`, `update`, `authenticate`, `sync`).

## 3. Resolve ownership and relations safely

- Scope relation lookups to the authenticated user when ownership applies.
- Fail fast when related entities do not belong to the caller.
- Keep relation-resolution helpers private and reusable.

## 4. Use transaction boundaries for multi-step writes

- Wrap multi-model writes in `DB::transaction()`.
- Keep transaction boundaries in the service layer.
- Avoid partial writes by ensuring failures roll back atomically.

## 5. Use exception-first error flow

- Throw domain-relevant exceptions for business failures.
- Do not use tuple-style error returns in PHP.
- Map exceptions to GraphQL/HTTP responses at the edge layer.

## 6. Validate behavior with feature tests

- Add/update Feature tests for success and failure paths.
- Verify authorization and rollback behavior explicitly.
- Keep service behavior aligned with schema/controller contracts.

## Output checklist

- Business logic is in focused services under `app/Services/`.
- Controllers and GraphQL classes remain thin.
- Transactional writes are atomic and ownership-safe.
- Error flow is exception-first and transport-safe.

For project-specific file paths and examples, read `references/project-conventions.md`.
