# GraphQL Standards

Use these conventions in this repository.

## Schema layout

- Entry schema: `graphql/schema.graphql`
- Generated printable schema: `graphql/schema.generated.graphql`

## Current directive patterns

- Authenticated fields: `@guard` and `@auth`
- Input validation: `@rules`
- Custom orchestration: `@field(resolver: \"App\\\\GraphQL\\\\...\" )`

## Current resolver placement

- GraphQL mutation classes: `app/GraphQL/Mutations/`
- GraphQL query classes:  `app/GraphQL/Queries/`

## Response shaping

- For auth flows, prefer explicit union response branches with `__typename`.
- Keep mutation handlers deterministic and throw on unsupported statuses.

## Supporting examples

- `app/GraphQL/Mutations/AuthCodeMutations.php`
- `tests/Feature/GraphQL/AuthCodeMutationsTest.php`
