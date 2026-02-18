# Testing Standards

Use these conventions in this repository.

## Framework setup

- Test runner: Vitest (`vitest.config.ts`)
- Test setup file: `resources/js/tests/setup.ts`
- MSW server: `resources/js/tests/msw/server.ts`

## Current setup behavior

- `server.listen({ onUnhandledRequest: \"error\" })` runs in `beforeAll`.
- `server.resetHandlers()`, cookie clearing, and localStorage clearing run in `afterEach`.
- `server.close()` runs in `afterAll`.

## Commands

- Run frontend tests: `npm run test`
- Run type checks: `npm run typecheck`

## Practical rules

- Keep auth flow tests aligned with `resources/js/lib/authSession.ts`, `resources/js/lib/authRedirect.ts`, and `resources/js/stores/sessionStore.ts`.
- Prefer testing behavior through public hooks/components over private helper internals.
