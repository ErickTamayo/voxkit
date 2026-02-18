---
name: graphql-lighthouse-pattern
description: Build and refactor Lighthouse GraphQL schema and resolver flows with directive-first modeling, explicit validation and authorization, and thin mutation/query classes. Use when changing graphql/*.graphql or GraphQL resolver behavior.
---

# GraphQL Lighthouse Pattern

Follow this workflow when adding or modifying GraphQL-backed backend behavior.

## 1. Model schema first

- Update `graphql/*.graphql` before resolver/service changes.
- Keep type/input names explicit and stable.
- Request only fields needed for current behavior.
- Keep schema changes minimal and intentional.

## 2. Prefer directive-first implementation

- Use `@guard` for protected operations.
- Use `@rules` on input fields for validation.
- Use relation directives when the behavior is direct relationship mapping.
- Use `@field` resolvers only for custom orchestration.

## 3. Keep authorization explicit

- Use policy directives (`@can`, `@canFind`, `@canModel`) when ownership rules apply.
- Enforce ownership constraints in service/resolver logic when directives are insufficient.
- Avoid implicit authorization assumptions.

## 4. Keep mutation/query classes thin

- Keep argument mapping and response shaping in GraphQL classes.
- Delegate business logic to `app/Services/`.
- Avoid embedding large validation or relation-resolution blocks in GraphQL classes.

## 5. Validate with GraphQL tests

- Update `tests/Feature/GraphQL/` alongside schema changes.
- Cover success, validation failure, and auth/authorization failure.
- Assert response payload shape and union branches explicitly.

## Output checklist

- Schema and resolver/service code are aligned.
- Validation and authorization are explicit.
- Mutation/query classes remain orchestration-only.
- GraphQL tests cover critical success/failure paths.

For repository-specific schema conventions, read `references/graphql-standards.md`.
