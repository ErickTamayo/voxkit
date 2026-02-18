---
name: testing-with-pest-graphql
description: Write deterministic backend tests with Pest for REST and Lighthouse GraphQL behavior, including authentication guards, union response assertions, and database side effects. Use when adding or fixing backend features and regressions.
---

# Testing With Pest And GraphQL

Follow this workflow when adding or updating backend tests.

## 1. Define behavior under test

- Anchor each test to one observable behavior.
- Cover success and failure paths for each changed behavior.
- Keep test names behavior-specific.

## 2. Choose the right test layer

- Use Feature tests for HTTP, GraphQL, database integration, and auth/policy behavior.
- Use Unit tests for isolated pure/domain logic.
- Keep GraphQL tests in `tests/Feature/GraphQL/`.

## 3. Build deterministic context

- Use factories for realistic data setup.
- Use Laravel auth helpers for session/token contexts.
- Keep external side effects mocked/faked where relevant.
- Reset shared state between tests.

## 4. Assert contracts explicitly

- For GraphQL success cases, assert successful responses and payload branches.
- For GraphQL errors, assert exact message/shape where applicable.
- Assert database side effects with `assertDatabaseHas/Missing/Count`.
- Assert union `__typename` for branch-specific behavior.

## 5. Keep suite maintainable

- Keep setup readable with `beforeEach` and focused helpers.
- Keep fixtures local unless repeated enough to extract.
- Run targeted files first, then broader suites.

## Output checklist

- Tests cover success and failure branches for changed behavior.
- Auth and policy constraints are explicitly verified.
- DB and side-effect assertions match expected contracts.
- Schema changes and test operations remain aligned.

For repository-specific commands and test patterns, read `references/testing-standards.md`.
