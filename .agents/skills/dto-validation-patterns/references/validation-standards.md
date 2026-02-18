# Validation Standards

Use these conventions in this repository.

## GraphQL validation boundary

- GraphQL inputs are validated in schema via `@rules`.
- Keep input shape constraints in `graphql/schema.graphql`.

## REST validation boundary

- Use Form Request classes for non-GraphQL controllers.
- Do not validate ad-hoc in controller bodies when a reusable request class is appropriate.

## Mapping conventions

- Keep mutation/controller classes focused on mapping input to service calls.
- Keep parsing/normalization logic reusable when it appears in multiple places.

## Current examples

- `graphql/schema.graphql` (`RequestAuthenticationCodeInput`, `AuthenticateWithCodeInput`)
- `app/GraphQL/Mutations/AuthCodeMutations.php`
- `tests/Feature/GraphQL/AuthCodeMutationsTest.php`
