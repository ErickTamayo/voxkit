---
name: testing-with-msw-vitest
description: Write deterministic frontend tests with Vitest and Mock Service Worker (MSW) for API-backed behavior. Use when adding or modifying UI/data-flow tests that depend on network responses, auth flows, or error handling.
---

# Testing With MSW And Vitest

Follow this workflow for API-backed frontend tests.

## 1. Define behavior-level test cases

- Test one user-visible behavior per case.
- Assert rendered outcomes and state transitions, not implementation details.
- Cover both success and failure paths for changed behavior.
- Structure tests in clear `Arrange -> Act -> Assert` sections (comments are fine when helpful).

## 2. Model network behavior with MSW

- Add handlers for the exact route/method under test.
- Keep payloads minimal but realistic.
- Use per-test handler overrides for scenario-specific responses.

## 3. Keep tests deterministic

- Use shared setup from the project test bootstrap.
- For React-rendering tests, prefer React Testing Library (`render`, `renderHook`) instead of manual `createRoot` / custom probe components.
- Follow React Testing Library best practices in React-rendering tests:
  - prefer JSX test fixtures over `React.createElement(...)`
  - use `.test.tsx` / `.spec.tsx` when the test renders JSX
  - prefer user-facing queries from RTL (`screen`/query helpers) over DOM plumbing when practical
- Wait for async transitions with testing-library async utilities.
- Reset handlers, cookies, and local storage between tests.
- Avoid real network calls in tests.

## 4. Assert auth and error contracts

- Verify unauthenticated behavior (redirect/session reset/error state) explicitly.
- Verify expected fallback messaging for transport and GraphQL failures.
- Verify success-path side effects (token/session storage, redirects) where relevant.

## 5. Keep suite maintainable

- Keep fixtures local until repeated enough to extract.
- Use stable module-level helpers for common rendering/mocking patterns.
- Keep test names behavior-specific and readable.

## Output checklist

- Success/failure branches are covered for changed behavior.
- MSW handlers are scoped and reset safely.
- Assertions reflect user-visible outcomes.
- No test depends on real network or shared mutable global state.

For project-specific setup and commands, read `references/testing-standards.md`.
