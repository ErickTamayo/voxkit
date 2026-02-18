# Project Conventions

Use these conventions in this repository.

## Backend layout

- Services: `app/Services/`
- GraphQL mutations: `app/GraphQL/Mutations/`
- GraphQL schema: `graphql/schema.graphql`
- Feature tests: `tests/Feature/`
- GraphQL feature tests: `tests/Feature/GraphQL/`

## Current architecture examples

- `app/Services/AuthenticationService.php`
- `app/Services/AuthCodeService.php`
- `app/GraphQL/Mutations/AuthCodeMutations.php`

## Anti-pattern reminders

- Do not grow mutation/controller classes into business-logic classes.
- Do not duplicate validation logic across schema, controller, and service layers.
- Do not merge GraphQL behavior changes without matching Feature tests.
